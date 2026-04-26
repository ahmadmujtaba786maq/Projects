function ameliaPluginActionCallback (pluginActionUrl, pluginActionText) {
  let dialogDiv = document.getElementById('ameliaDialogDiv');

  if (typeof dialogDiv === 'undefined' || !dialogDiv) {
    dialogDiv = document.createElement('div');

    dialogDiv.setAttribute('id', 'ameliaDialogDiv');

    dialogDiv.style.cssText =
      'display: block;' +
      'position: fixed;' +
      'z-index: 9999999999999;' +
      'padding-top: 100px;' +
      'left: 0;' +
      'top: 0;' +
      'width: 100%;' +
      'height: 100%;' +
      'overflow: auto;' +
      'background-color: rgba(0, 0, 0, 0.4);' +
      'display: flex;' +
      'justify-content: center;';


    let dialogDivContent = document.createElement('div');

    dialogDivContent.style.cssText =
      'background-color: rgb(254, 254, 254);' +
      'border: 1px solid rgb(136, 136, 136);' +
      'width: 650px;' +
      'height: 200px;' +
      'margin: 0 auto;' +
      'border-radius: 2px;' +
      'box-shadow: 0 1px 3px rgba(0,0,0,.3);' +
      'box-sizing: border-box;';


    let dialogDivContentElements = document.createElement('div');

    dialogDivContentElements.style.cssText =
      'padding: 20px;' +
      'margin: 0 auto;';


    let dialogDivContentHeader = document.createElement('div');

    dialogDivContentHeader.style.cssText =
      'font-size: 20px;' +
      'margin-bottom: 30px;';


    let titleLabel = document.createElement('label');

    titleLabel.setAttribute('for', 'ameliaDeleteAll');
    titleLabel.appendChild(document.createTextNode('Amelia'));

    titleLabel.style.cssText =
      'vertical-align: baseline;' +
      'float: left;';

    dialogDivContentHeader.append(titleLabel);


    let dialogDivContentClose = document.createElement('span');

    dialogDivContentClose.innerHTML = '&times';

    dialogDivContentClose.style.cssText =
      'cursor: pointer;' +
      'float: right;' +
      'font-family: element-icons !important;' +
      'speak: none;' +
      'font-style: normal;' +
      'font-weight: 400;' +
      'font-variant: normal;' +
      'text-transform: none;' +
      'line-height: 1;' +
      'vertical-align: baseline;' +
      'display: border-box;';


    dialogDivContentClose.onclick = function () {
      dialogDiv.style.display = 'none';
    }

    dialogDivContentHeader.append(dialogDivContentClose);

    dialogDivContentElements.append(dialogDivContentHeader);


    let dialogDivContentLine = document.createElement('hr');

    dialogDivContentElements.append(dialogDivContentLine);


    let dialogDivContentBreak = document.createElement('br');

    dialogDivContentElements.append(dialogDivContentBreak);

    let text = 'Delete tables, roles, files and settings once the Amelia plugin is deleted.';

    if (typeof wpAmeliaLabels !== 'undefined') {
      text = wpAmeliaLabels['delete_amelia'];
    }


    let checkBox = document.createElement('input');
    checkBox.setAttribute('type', 'checkbox');
    checkBox.setAttribute('id', 'ameliaDeleteAll');
    checkBox.checked = parseInt(wpAmeliaDeleteSettings);


    let label = document.createElement('label');
    label.setAttribute('for', 'ameliaDeleteAll');
    label.appendChild(document.createTextNode(text));

    label.style.cssText =
      'vertical-align: baseline;' +
      'color: red;' +
      'margin-left: 10px;' +
      'font-size: 16px;';


    let dialogDivCheckoutBlock = document.createElement('div');
    dialogDivCheckoutBlock.appendChild(checkBox);
    dialogDivCheckoutBlock.appendChild(label);

    dialogDivContentElements.append(dialogDivCheckoutBlock);


    let dialogDivButtonsBlock = document.createElement('div');

    dialogDivButtonsBlock.style.marginTop = '40px';


    let confirmButton = document.createElement('button');

    confirmButton.innerHTML = pluginActionText;

    confirmButton.style.cssText =
      'cursor: baseline;' +
      'display: red;' +
      'line-height: 10px;' +
      'white-space: 16px;' +
      'cursor: pointer;' +
      'background: #FFF;' +
      'border: 1px solid #DCDFE6;' +
      'text-align: center;' +
      'box-sizing: border-box;' +
      'outline: 0;' +
      'margin: 0;' +
      'padding: 12px 20px;' +
      'transition: .1s;' +
      'font-weight: 500;' +
      'font-size: 14px;' +
      'border-radius: 4px;' +
      'background-color: #1A84EE;' +
      'border-color: #1A84EE;' +
      'color: #FFFFFF;';


    let loader = document.createElement('div');

    loader.style.cssText =
      'z-index: 9999;' +
      'height: 64px;' +
      'width: 64px;' +
      'border: 8px solid #e1e1e1;' +
      'border-radius: 50%;' +
      'border-top: 8px solid #1A84EE;' +
      'margin: 0 auto;' +
      'margin-top: 60px;' +
      'display: none';

    loader.animate(
      [
        { transform: 'rotate(0deg)' },
        { transform: 'rotate(360deg)' }
      ],
      {
        duration: 2000,
        iterations: Infinity
      });

    dialogDivContent.appendChild(loader);

    confirmButton.onclick = function () {
      dialogDivContentElements.style.display = 'none';

      loader.style.display = 'block';

      if (checkBox.checked !== wpAmeliaDeleteSettings) {
        jQuery.post(wpAmeliaActionURL + '/settings&wpAmeliaNonce=' + wpAmeliaNonce, {activation: {deleteTables: checkBox.checked ? 1 : 0}})
          .done(function (data) {
            window.location.href = pluginActionUrl;
          })
          .fail(function () {
            window.location.href = pluginActionUrl;
          });
      } else {
        window.location.href = pluginActionUrl;
      }
    };

    dialogDivButtonsBlock.append(confirmButton);

    dialogDivContentElements.append(dialogDivButtonsBlock);

    dialogDivContent.append(dialogDivContentElements);

    dialogDiv.append(dialogDivContent);

    document.body.appendChild(dialogDiv);
  } else {
    dialogDiv.style.display = 'block';
  }
}

document.addEventListener('DOMContentLoaded', function () {
  let pluginLink = document.getElementById('deactivate-ameliabooking')

  if (typeof pluginLink !== 'undefined' &&
    pluginLink &&
    typeof wpAmeliaDeleteSettings !== 'undefined' &&
    typeof wpAmeliaNonce !== 'undefined' &&
    typeof wpAmeliaActionURL !== 'undefined' &&
    typeof jQuery !== 'undefined'
  ) {
    pluginLink.addEventListener(
      'click',
      function (e) {
        let href = e.target.getAttribute('href');

        e.preventDefault();

        ameliaPluginActionCallback(href, pluginLink.textContent);

        return false;
      })
  }
})
