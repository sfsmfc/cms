/** global: Craft */
/** global: Garnish */
/**
 * IntervalAnnouncer class
 */
Craft.IntervalAnnouncer = Garnish.Base.extend(
  {
    _intervalId: null,
    messageCallback: null,

    init: function (messageCallback, settings) {
      this.setSettings(settings, Craft.IntervalAnnouncer.defaults);

      this.messageCallback = messageCallback;
      this.start();
    },

    start: function () {
      if (!this.messageCallback) return;

      this._intervalId = setInterval(() => {
        Craft.cp.announce(this.messageCallback);
      }, this.settings.interval);
    },

    stop: function () {
      clearInterval(this._intervalId);
      this._intervalId = null;
    },
  },
  {
    defaults: {
      interval: 100,
    },
  }
);
