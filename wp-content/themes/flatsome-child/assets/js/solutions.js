(function () {
  var nodes = document.querySelectorAll('.sa-sol-reveal');
  if (!nodes.length || !('IntersectionObserver' in window)) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-in');
      io.unobserve(entry.target);
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
  nodes.forEach(function (node) {
    var box = node.getBoundingClientRect();
    if (box.top < window.innerHeight * 0.94) node.classList.add('is-in');
    else io.observe(node);
  });
  document.documentElement.classList.add('sa-sol-motion');
})();
