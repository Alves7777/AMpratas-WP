// Comportamento da vitrine, igual ao resources/js/app.js da Laravel.
document.addEventListener('DOMContentLoaded', function () {
  var header = document.getElementById('site-header');
  var toggle = document.querySelector('[data-toggle-nav]');
  var mobileNav = document.getElementById('mobile-nav');

  if (header) {
    var onScroll = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  if (toggle && mobileNav) {
    toggle.addEventListener('click', function () {
      mobileNav.classList.toggle('hidden');
    });
  }
});
