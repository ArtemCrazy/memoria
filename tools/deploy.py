"""
Deploy kipora theme/plugin (and tests) to the test server over SFTP.

Credentials come from the CrazyAssistant creds file outside the project;
nothing secret lives in the repository.

  python tools/deploy.py            upload plugin + theme
  python tools/deploy.py --tests    also upload tests/ and run them with server PHP
  python tools/deploy.py --run "cmd"  run a shell command in the site folder
"""

import os
import posixpath
import sys

import paramiko

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CREDS = os.environ.get(
    "KIPORA_CREDS",
    r"C:\Users\CrazyStudio\AppData\Local\CrazyAssistant\creds\card-306.env",
)
PHP = "/usr/local/php-cgi/8.2/bin/php"

# What goes to the server. .claude/ and dev files never do.
TARGETS = ["wp-content/plugins/kipora-core", "wp-content/themes/kipora"]
SKIP_DIRS = {".claude", ".git", "node_modules", "scss", ".sass-cache"}
SKIP_EXT = {".map", ".md"}


def load_env(path):
    env = {}
    with open(path, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if "=" in line and not line.startswith("#"):
                k, v = line.split("=", 1)
                env[k.strip()] = v.strip().strip('"').strip("'")
    return env


def connect():
    env = load_env(CREDS)
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(env["SFTP_HOST"], int(env.get("SFTP_PORT", 22)), env["SFTP_USER"], env["SFTP_PASSWORD"], timeout=30)
    return client, env.get("SFTP_DIR", "memoria")


def ensure_dir(sftp, path):
    parts = path.split("/")
    for i in range(1, len(parts) + 1):
        p = "/".join(parts[:i])
        try:
            sftp.stat(p)
        except IOError:
            sftp.mkdir(p)


def upload_tree(sftp, local_root, remote_root):
    count = 0
    for dirpath, dirnames, filenames in os.walk(local_root):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        rel = os.path.relpath(dirpath, local_root).replace("\\", "/")
        remote_dir = remote_root if rel == "." else posixpath.join(remote_root, rel)
        ensure_dir(sftp, remote_dir)
        for name in filenames:
            if os.path.splitext(name)[1] in SKIP_EXT:
                continue
            sftp.put(os.path.join(dirpath, name), posixpath.join(remote_dir, name))
            count += 1
    return count


def run(client, site_dir, cmd):
    _, out, err = client.exec_command(f"cd {site_dir} && {cmd}", timeout=600)
    code = out.channel.recv_exit_status()
    sys.stdout.buffer.write(out.read())
    sys.stdout.buffer.write(err.read())
    return code


def stat_is_dir(sftp, path):
    import stat
    return stat.S_ISDIR(sftp.stat(path).st_mode)


def download_tree(sftp, remote_root, local_root):
    import stat
    os.makedirs(local_root, exist_ok=True)
    for entry in sftp.listdir_attr(remote_root):
        remote = posixpath.join(remote_root, entry.filename)
        local = os.path.join(local_root, entry.filename)
        if stat.S_ISDIR(entry.st_mode):
            download_tree(sftp, remote, local)
        else:
            sftp.get(remote, local)


RUNNER_ENV = os.path.join(os.path.dirname(CREDS), "card-306-runner.env")
RUNNER_PHP = """<?php
// Dev-only runner for KIPORA maintenance scripts on the TEST server.
// Not part of the repository; delete before handing over the site.
if ( isset( $_GET['kp_run'], $_GET['script'] ) && hash_equals( '%s', (string) $_GET['kp_run'] ) ) {
\tadd_action( 'wp_loaded', static function () {
\t\theader( 'Content-Type: text/plain; charset=utf-8' );
\t\t$f = WP_CONTENT_DIR . '/database/tests/' . basename( (string) $_GET['script'] ) . '.php';
\t\tif ( is_file( $f ) ) { require $f; } else { echo 'no script'; }
\t\texit;
\t}, 1 );
}
"""


def wp_script(client, site_dir, name):
    """Upload tools/wp/<name>.php and run it inside WordPress via the dev runner."""
    import secrets
    import urllib.request

    if os.path.exists(RUNNER_ENV):
        token = load_env(RUNNER_ENV)["RUNNER_TOKEN"]
    else:
        token = secrets.token_hex(24)
        with open(RUNNER_ENV, "w", encoding="utf-8") as f:
            f.write(f"RUNNER_TOKEN={token}\n")
    sftp = client.open_sftp()
    ensure_dir(sftp, f"{site_dir}/wp-content/mu-plugins")
    with sftp.open(f"{site_dir}/wp-content/mu-plugins/kp-runner.php", "w") as f:
        f.write(RUNNER_PHP % token)
    ensure_dir(sftp, f"{site_dir}/wp-content/database/tests")
    sftp.put(os.path.join(ROOT, "tools", "wp", name + ".php"), f"{site_dir}/wp-content/database/tests/{name}.php")
    sftp.close()
    url = f"https://korovai.crazytest.ru/memoria/?kp_run={token}&script={name}"
    req = urllib.request.Request(url, headers={"Cookie": "beget=begetok"})
    try:
        with urllib.request.urlopen(req, timeout=300) as r:
            sys.stdout.buffer.write(r.read())
    except urllib.error.HTTPError as e:
        sys.stdout.buffer.write(e.read())
        return 1
    return 0


def main():
    args = sys.argv[1:]
    client, site_dir = connect()
    if "--wp" in args:
        sys.exit(wp_script(client, site_dir, args[args.index("--wp") + 1]))
    if "--run" in args:
        sys.exit(run(client, site_dir, args[args.index("--run") + 1]))
    if "--pull" in args:
        # Bring a server-built folder (e.g. composer vendor/) back into the repo.
        rel = args[args.index("--pull") + 1]
        sftp = client.open_sftp()
        remote = posixpath.join(site_dir, rel)
        if stat_is_dir(sftp, remote):
            download_tree(sftp, remote, os.path.join(ROOT, rel))
        else:
            sftp.get(remote, os.path.join(ROOT, rel))
        print(f"pulled {rel}")
        sys.exit(0)

    sftp = client.open_sftp()
    for target in TARGETS:
        local = os.path.join(ROOT, target)
        if os.path.isdir(local):
            n = upload_tree(sftp, local, posixpath.join(site_dir, target))
            print(f"{target}: {n} files")

    code = 0
    if "--tests" in args:
        upload_tree(sftp, os.path.join(ROOT, "tests"), posixpath.join(site_dir, "wp-content/database/tests"))
        code = run(client, site_dir, f"{PHP} wp-content/database/tests/run.php")
    sftp.close()
    client.close()
    sys.exit(code)


if __name__ == "__main__":
    main()
