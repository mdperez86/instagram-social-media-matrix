(function ($, root) {
  function resize() {
    $(root).each(function () {
      var colums = $(window).width() > 768 ? 5 : 3;
      var height = $(this).width() / colums;
      $(this).find('.media').css({ minHeight: height + 'px' });
    });
  }
  resize();
  $(window).resize(resize);
})(jQuery, '.instagram-social-media-matrix');
