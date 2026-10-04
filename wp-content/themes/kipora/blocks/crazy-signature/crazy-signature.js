/* ───────────────────────────────────────────────────────────────────────────
   Crazy Signature — оживляет подпись студии в подвале.

   Зависимостей нет. Работает и как ES-модуль (import), и как обычный скрипт
   (<script src="..." defer>) — во втором случае инициализируется сам.

   Что делает: по наведению вешает на подпись класс is-flying и снимает его,
   когда полёт доигран. Всё движение — в CSS.
   ─────────────────────────────────────────────────────────────────────────── */

const FLIGHT_NAMES = ['cs-sign-fly', 'cs-sign-fly-hang'];

/** Запускает один полёт. Повторный вызов во время полёта игнорируется —
 *  иначе анимация дёргалась бы при каждом движении курсора. */
function launch(sign) {
  if (sign.classList.contains('is-flying')) return;

  const bat = sign.querySelector('.cs-sign__bat');
  if (!bat) return;

  sign.classList.add('is-flying');

  // Снимаем класс по окончании полёта, а не по уходу курсора: иначе мышь
  // застывала бы в воздухе на полпути, если увести мышь сразу после наведения.
  const done = (event) => {
    if (!FLIGHT_NAMES.includes(event.animationName)) return;
    bat.removeEventListener('animationend', done);
    sign.classList.remove('is-flying');
  };
  bat.addEventListener('animationend', done);
}

/**
 * Ищет подписи и включает их.
 * @param {ParentNode} root  где искать (по умолчанию — весь документ)
 */
export function initCrazySignature(root = document) {
  const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  root.querySelectorAll('[data-cs-sign]').forEach((sign) => {
    if (sign.dataset.csSignReady === '1') return;
    sign.dataset.csSignReady = '1';

    if (calm) return;

    // focus, а не только mouseenter: подпись — ссылка, до неё доходят
    // и клавиатурой.
    sign.addEventListener('mouseenter', () => launch(sign));
    sign.addEventListener('focus', () => launch(sign));
  });
}

// Автозапуск. Нужен для случая «подключили тегом <script>»; при импорте
// модулем он просто отработает один раз и не помешает ручному вызову —
// повторную инициализацию защищает флаг data-cs-sign-ready.
if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initCrazySignature());
  } else {
    initCrazySignature();
  }
}
