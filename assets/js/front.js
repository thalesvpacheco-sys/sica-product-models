document.addEventListener('DOMContentLoaded', function () {
  const modules = document.querySelectorAll('.spm-front');

  if (!modules.length) {
    return;
  }

  modules.forEach(function (module) {
    const selectedInput = module.querySelector('.spm-front__selected-model');
    const selectedSlug = module.getAttribute('data-selected-slug') || '';

    if (selectedInput) {
      selectedInput.value = selectedSlug;
    }

    module.querySelectorAll('.spm-front__option[data-model-url]').forEach(function (element) {
      if (element.tagName.toLowerCase() === 'a') {
        return;
      }

      element.addEventListener('click', function () {
        const url = element.getAttribute('data-model-url');

        if (!url) {
          return;
        }

        window.location.href = url;
      });
    });

    module.querySelectorAll('.spm-front__reset[data-reset-url]').forEach(function (element) {
      if (element.tagName.toLowerCase() === 'a') {
        return;
      }

      element.addEventListener('click', function () {
        const url = element.getAttribute('data-reset-url');

        if (!url) {
          return;
        }

        window.location.href = url;
      });
    });

    var navigating = false;

    module.querySelectorAll('.spm-front__option, .spm-front__reset').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        if (navigating) {
          e.preventDefault();
          return;
        }

        navigating = true;

        module.querySelectorAll('.spm-front__option, .spm-front__reset').forEach(function (el) {
          el.classList.add('is-navigating');
        });
      });
    });
  });
});