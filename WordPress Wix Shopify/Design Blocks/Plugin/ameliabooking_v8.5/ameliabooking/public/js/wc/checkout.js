function setAmeliaFieldValue(selector, value) {
  let element = document.querySelector(selector);

  if (typeof element !== 'undefined' && element) {
    const valueSetter = Object.getOwnPropertyDescriptor(element, 'value').set;
    const prototype = Object.getPrototypeOf(element);
    const prototypeValueSetter = Object.getOwnPropertyDescriptor(prototype, 'value').set;

    if (valueSetter && valueSetter !== prototypeValueSetter) {
      prototypeValueSetter.call(element, value);
    } else {
      valueSetter.call(element, value);
    }

    element.dispatchEvent(new Event('input', { bubbles: true }));
  }
}

document.addEventListener('DOMContentLoaded', function() {
  if ('ameliaCustomer' in window) {
    let ameliaCustomerInterval = setInterval(
      function () {
        if (document.body.classList.contains('woocommerce-checkout')) {
          clearInterval(ameliaCustomerInterval);

          Object.keys(ameliaCustomer).forEach((key) => {
            setAmeliaFieldValue('#' + key, ameliaCustomer[key]);
          })
        }
      }, 500
    )

    const injectItemContent = (block, index) => {
      let el = block.querySelector('.wc-block-components-product-details');

      if (el !== null && 'ameliaNote' + index in window && !el.querySelector('.amelia-custom-html')) {
        const div = document.createElement('div');
        div.className = 'amelia-custom-html';
        div.innerHTML = '<div>' + window['ameliaNote' + index][0] + '</div>';
        el.appendChild(div);
      }
    }

    const injectCustomContent = () => {
      document.querySelectorAll('.wc-block-cart-items__row').forEach((block, index) => {
        injectItemContent(block, index)
      });

      document.querySelectorAll('.wc-block-components-order-summary-item').forEach((block, index) => {
        injectItemContent(block, index)
      });
    };

    injectCustomContent();

    // Use MutationObserver to handle async block rendering
    const observer = new MutationObserver(injectCustomContent);
    observer.observe(document.body, { childList: true, subtree: true });
  }
});

const ameliaStyleTag = document.createElement('style');

ameliaStyleTag.innerHTML = `
    .wc-block-components-product-details__appointment-info {
        display: none
    }
`;

document.head.appendChild(ameliaStyleTag);
