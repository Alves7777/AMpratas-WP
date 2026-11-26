(function () {
  if (!window.wc || !window.wc.wcBlocksRegistry || !window.wc.wcSettings || !window.wp) {
    return;
  }

  var settings = window.wc.wcSettings.getSetting('ampratas_data', {});
  var decode = window.wp.htmlEntities.decodeEntities;
  var el = window.wp.element.createElement;
  var label = decode(settings.title || 'Pagamento AMpratas');

  var Content = function () {
    return el(
      'div',
      { className: 'ampratas-block-description' },
      decode(settings.description || '')
    );
  };

  window.wc.wcBlocksRegistry.registerPaymentMethod({
    name: 'ampratas',
    label: label,
    content: el(Content, null),
    edit: el(Content, null),
    canMakePayment: function () {
      return true;
    },
    ariaLabel: label,
    supports: {
      features: settings.supports || ['products'],
    },
  });
})();
