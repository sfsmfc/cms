/** global: Craft */
/** global: Garnish */
/**
 * Sortable checkbox select class
 */
Craft.SortableCheckboxSelect = Garnish.Base.extend(
  {
    $container: null,

    init: function (containerId, settings) {
      this.$container = $('#' + containerId);

      this.setSettings(settings, Craft.SortableCheckboxSelect.defaults);

      const sortItems = this.$container.find('.draggable');
      if (sortItems.length) {
        let dragSort = new Garnish.DragSort(sortItems, {
          axis: Garnish.Y_AXIS,
          handle: '.draggable-handle',
        });
      }

      this.$container.find('.draggable').each((key, item) => {
        this.initItem(item);
      });
    },

    initItem: function (item) {
      return new Craft.SortableCheckboxSelect.Item(this, item);
    },
  },
  {
    defaults: {},
  }
);

Craft.SortableCheckboxSelect.Item = Garnish.Base.extend(
  {
    $selectContainer: null,
    $item: null,
    $actionMenu: null,
    actionDisclosure: null,
    $actionMenuOptions: null,

    init: function (select, item, settings) {
      this.$selectContainer = select.$container;
      this.$item = $(item);

      this.setSettings(settings, Craft.SortableCheckboxSelect.Item.defaults);

      const $actionMenuBtn = this.$item.find('> .action-btn');
      const actionDisclosure =
        $actionMenuBtn.data('trigger') ||
        new Garnish.DisclosureMenu($actionMenuBtn);

      this.$actionMenu = actionDisclosure.$container;
      this.actionDisclosure = actionDisclosure;

      actionDisclosure.on('show', () => {
        this.$item.addClass('active');
        if (this.$item.prev('.draggable').length) {
          this.$actionMenu
            .find('button[data-action=moveUp]:first')
            .parent()
            .removeClass('hidden');
        } else {
          this.$actionMenu
            .find('button[data-action=moveUp]:first')
            .parent()
            .addClass('hidden');
        }
        if (this.$item.next('.draggable').length) {
          this.$actionMenu
            .find('button[data-action=moveDown]:first')
            .parent()
            .removeClass('hidden');
        } else {
          this.$actionMenu
            .find('button[data-action=moveDown]:first')
            .parent()
            .addClass('hidden');
        }
      });

      this.$actionMenuOptions = this.$actionMenu.find('button[data-action]');

      this.addListener(
        this.$actionMenuOptions,
        'activate',
        this.handleActionClick
      );
    },

    handleActionClick: function (event) {
      event.preventDefault();
      this.onActionSelect(event.target);
    },

    onActionSelect: function (option) {
      const $option = $(option);

      switch ($option.data('action')) {
        case 'moveUp': {
          this.moveUp();
          break;
        }

        case 'moveDown': {
          this.moveDown();
          break;
        }
      }

      this.actionDisclosure.hide();
    },

    moveUp: function () {
      let $prev = this.$item.prev('.draggable');
      if ($prev.length) {
        this.$item.insertBefore($prev);
      }
    },

    moveDown: function () {
      let $next = this.$item.next('.draggable');
      if ($next.length) {
        this.$item.insertAfter($next);
      }
    },
  },
  {
    defaults: {},
  }
);
