<?php
// Drop rewrite rules: WordPress rebuilds them on the next normal request, with Polylang fully loaded.
defined( "ABSPATH" ) || exit;
delete_option( "rewrite_rules" );
echo "rules dropped
";
