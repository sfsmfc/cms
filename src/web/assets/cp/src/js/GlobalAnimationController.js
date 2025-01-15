/**
 * Global Animation Controller Class
 */

Craft.GlobalAnimationController = Garnish.Base.extend({
  $images: null,
  resizeObserver: null,

  init: function () {
    this.$images = $();
    const $images = this.getPotentiallyAnimatedImages();

    // Create resize observer
    this.resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        if (entry.contentBoxSize) {
          const $targetImage = $(entry.target);

          if (entry.contentRect.width > 0 || entry.contentRect.height > 0) {
            this.pauseImage($targetImage);
          }
        }
      }
    });

    this.addImages($images);
  },

  getPotentiallyAnimatedImages: function (container = Garnish.$doc) {
    const $container = $(container);

    return $(container).find(
      'img[src*=".gif"],img[srcset*=".gif"],img[src*=".webp"],img[srcset*=".webp"]'
    );
  },

  addImagesInContainer: function (container) {
    const $animated = this.getPotentiallyAnimatedImages(container);

    if ($animated.length === 0) return;

    this.addImages($animated);
  },

  addImages: function ($images) {
    if ($images.length === 0) return;

    // Add images to collection
    this.$images = this.$images.add($images);

    // Go through each image and create toggle + cover
    for (let i = 0; i < $images.length; i++) {
      const $image = $($images[i]);

      // If image has already been added, return
      if ($image.data('animation-controller')) {
        console.warn('Image has already been added to animation controller');
        return;
      }

      if ($image[0].complete) {
        this.pauseImage($image);
        this.createToggle($image);
        this.resizeObserver.observe($image[0]);
      } else {
        this.addListener($image, 'load', () => {
          this.pauseImage($image);
          this.createToggle($image);
          this.resizeObserver.observe($image[0]);
        });
      }

      $image.data('animation-controller', this);
    }
  },

  getToggleEnabled: function (image) {
    return !$(image).attr('data-disable-toggle');
  },

  getAnimationToggleButton: function (image) {
    return $(image).parent().find('[data-animation-toggle]');
  },

  getAnimationCoverImage: function (image) {
    return $(image).parent().find('canvas');
  },

  imageSizeChanged: function (image) {
    const $image = $(image);
    const width = $image.width();
    const height = $image.height();
    const prevWidth = $image.attr('data-width');
    const prevHeight = $image.attr('data-height');

    if (!prevWidth || !prevHeight) return;

    return (
      width !== parseInt(prevWidth, 10) || height !== parseInt(prevHeight, 10)
    );
  },

  getCanvas: function (image) {
    const $image = $(image);
    const width = $image.width();
    const height = $image.height();

    const $canvas = $('<canvas></canvas>')
      .attr({
        width: width,
        height: height,
        'aria-hidden': 'true',
        role: 'presentation',
        'data-image-cover': true,
      })
      .css({
        position: 'absolute',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
      });

    // Draw first frame on canvas
    $canvas[0].getContext('2d').drawImage($image[0], 0, 0, width, height);

    return $canvas;
  },

  pauseImage: function (image) {
    const $image = $(image);
    const $parent = $image.parent();
    let $canvas = $parent.find('[data-image-cover]');
    const width = $image.width();
    const height = $image.height();

    if ($canvas.length === 0) {
      $image.attr({
        'data-width': width,
        'data-height': height,
      });
      $canvas = this.getCanvas($image);
      $parent.css({
        position: 'relative',
      });
      $canvas.insertBefore($image);
    } else if ($canvas.length > 0 && this.imageSizeChanged($image)) {
      console.log('redraw');
      // Replace canvas
      $canvas.remove();
      const $newCanvas = this.getCanvas($image);
      $newCanvas.insertBefore($image);
    }
  },

  createToggle: function (image) {
    if (!this.getToggleEnabled(image)) return;

    const $image = $(image);
    const $wrapper = $image.parent();

    const $toggle = $('<button/>', {
      type: 'button',
      'data-icon': 'play',
      'data-animation-state': 'paused',
      'data-animation-toggle': true,
      'aria-label': Craft.t('app', 'Play'),
      class: 'animated-image-toggle btn',
    });

    $wrapper.append($toggle);

    this.addListener($toggle, 'click', (ev) => {
      this.handleToggleClick(ev);
    });
  },

  handleToggleClick: function (event) {
    const $toggle = $(event.target);
    const isPaused = $toggle.attr('data-animation-state') === 'paused';
    const $image = $toggle.parent().find('img');

    if (isPaused) {
      this.play($image);
    } else {
      this.pause($image);
    }
  },

  pauseAll: function () {
    for (let i = 0; i < this.$images.length; i++) {
      this.pause(this.$images[i]);
    }
  },

  pause: function (image) {
    const $image = $(image);
    const $coverImage = this.getAnimationCoverImage($image);
    const $toggleBtn = this.getAnimationToggleButton($image);

    $coverImage.removeClass('hidden');
    $toggleBtn.attr({
      'aria-label': Craft.t('app', 'Play'),
      'data-animation-state': 'paused',
      'data-icon': 'play',
    });
  },

  playAll: function () {
    for (let i = 0; i < this.$images.length; i++) {
      this.play(this.$images[i]);
    }
  },

  play: function (image) {
    const $image = $(image);
    const $coverImage = this.getAnimationCoverImage($image);
    const $toggleBtn = this.getAnimationToggleButton($image);

    $coverImage.addClass('hidden');
    $toggleBtn.attr({
      'aria-label': Craft.t('app', 'Pause'),
      'data-animation-state': 'playing',
      'data-icon': 'pause',
    });
  },
});
