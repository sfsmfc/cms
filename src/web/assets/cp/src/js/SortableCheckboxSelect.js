/** global: Craft */
/** global: Garnish */
/**
 * Sortable checkbox select class
 */
Craft.SortableCheckboxSelect = Garnish.Base.extend({
  $container: null,

  init: function (container) {
    this.$container = $(container);
    this.$container.data('sortableCheckboxSelect', this);

    const $sortItems = this.$container.children(
      '.checkbox-select-item:not(.all)'
    );
    if ($sortItems.length) {
      new Garnish.DragSort($sortItems, {
        axis: Garnish.Y_AXIS,
        handle: '.draggable-handle',
      });

      $sortItems.each((key, item) => {
        this.initItem(item);
      });
    }
  },

  initItem: function (item) {
    return new Craft.SortableCheckboxSelect.Item(this, item);
  },
});

Craft.SortableCheckboxSelect.Item = Garnish.Base.extend({
  select: null,
  $item: null,
  $moveHandle: null,
  $checkbox: null,
  $actionMenuBtn: null,
  $actionMenu: null,
  actionDisclosure: null,
  moveUpBtn: null,
  moveDownBtn: null,

  init: function (select, item) {
    this.select = select;
    this.$item = $(item);
    this.$moveHandle = this.$item.children('.move');
    this.$checkbox = this.$item.children('input[type=checkbox]');

    this.addListener(this.$checkbox, 'change', () => {
      this.handleCheckboxChange();
    });

    this.handleCheckboxChange();
  },

  handleCheckboxChange: function () {
    if (this.$checkbox.prop('checked')) {
      this.onCheck();
    } else {
      this.onUncheck();
    }
  },

  onCheck: function () {
    if (this.$actionMenuBtn) {
      this.onUncheck();
    }

    this.$moveHandle.removeClass('disabled');

    const menuId = 'menu-' + Math.floor(Math.random() * 1000000000);
    this.$actionMenuBtn = $('<button/>', {
      class: 'btn action-btn',
      'aria-controls': menuId,
      'aria-label': Craft.t('app', 'Actions'),
      'data-disclosure-trigger': '',
      'data-icon': 'ellipsis',
    }).appendTo(this.$item);
    this.$actionMenu = $('<div/>', {
      id: menuId,
      class: 'menu menu--disclosure',
    }).appendTo(this.$item);

    this.actionDisclosure = new Garnish.DisclosureMenu(this.$actionMenuBtn);
    this.moveUpBtn = this.actionDisclosure.addItem({
      icon: 'arrow-up',
      label: Craft.t('app', 'Move up'),
      onActivate: () => {
        this.moveUp();
      },
    });
    this.moveDownBtn = this.actionDisclosure.addItem({
      icon: 'arrow-down',
      label: Craft.t('app', 'Move down'),
      onActivate: () => {
        this.moveDown();
      },
    });

    this.actionDisclosure.on('show', () => {
      if (this.getPrevCheckedItem()) {
        this.actionDisclosure.showItem(this.moveUpBtn);
      } else {
        this.actionDisclosure.hideItem(this.moveUpBtn);
      }
      if (this.getNextCheckedItem()) {
        this.actionDisclosure.showItem(this.moveDownBtn);
      } else {
        this.actionDisclosure.hideItem(this.moveDownBtn);
      }
    });
  },

  onUncheck: function () {
    this.$moveHandle?.addClass('disabled');
    this.$actionMenuBtn?.remove();
    this.$actionMenu?.remove();
    this.actionDisclosure?.destroy();
    this.$actionMenuBtn = this.actionDisclosure = null;
  },

  getPrevCheckedItem: function () {
    const $item = this.$item.prevAll(
      '.checkbox-select-item:not(.all):has(input[type=checkbox]:checked):first'
    );
    return $item.length ? $item : null;
  },

  getNextCheckedItem: function () {
    const $item = this.$item.nextAll(
      '.checkbox-select-item:not(.all):has(input[type=checkbox]:checked):first'
    );
    return $item.length ? $item : null;
  },

  moveUp: function () {
    const $prev = this.getPrevCheckedItem();
    if ($prev) {
      this.$item.insertBefore($prev);
    }
  },

  moveDown: function () {
    const $next = this.getNextCheckedItem();
    if ($next) {
      this.$item.insertAfter($next);
    }
  },
});
