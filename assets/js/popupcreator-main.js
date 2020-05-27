(function ($) {
  var delayedPopups = [];
  var popupDisplayed = false;
  var haveExitPopup = false;
  $(document).ready(function () {
    PlainModal.closeByEscKey = false;
    PlainModal.closeByOverlay = false;
    var modalels = document.querySelectorAll('.modal-content');
    for (i = 0; i < modalels.length; i++) {
      var content = modalels[i];
      var modal = new PlainModal(content);
      var delay = modalels[i].getAttribute('data-delay');
      var autoHide = modalels[i].getAttribute('auto-hide');
      var modalId = modalels[i].getAttribute('data-modal-id');
      modal.closeButton = content.querySelector('.close-button'); // need to understand
      if (modalels[i].getAttribute('data-exit') != '1') {
        delayedPopups.push({
          modal: modal,
          delay: delay,
          exit: false,
          autoHide: autoHide,
          modalId: modalId,
        });
        runPopup(false);
      } else {
        delayedPopups.push({
          modal: modal,
          delay: delay,
          exit: true,
          autoHide: autoHide,
          modalId: modalId,
        });
        haveExitPopup = true;
      }
    }
  });
  function runPopup(exit) {
    for (i in delayedPopups) {
      if (exit == delayedPopups[i].exit) {
        setTimeout(
          function (i) {
            console.log(delayedPopups[i].modal);
            delayedPopups[i].modal.open();
            // delayedPopups[i].modal.close(true, { duration: 3000 });
            if ('yes' == delayedPopups[i].autoHide) {
              setTimeout(
                function (i) {
                  console.log(delayedPopups[i].modal);
                  delayedPopups[i].modal.close(true, { duration: 3000 });
                },
                3000,
                i
              );
            }
          },
          delayedPopups[i].delay,
          i
        );
      }
    }
  }
  $(document).ready(function () {
    if (haveExitPopup) {
      window.onbeforeunload = function () {
        if (!popupDisplayed) {
          runPopup(true);
          popupDisplayed = true;
          return 'random';
        }
      };
    }

    // inline css
    document.querySelector('.pop-text').style.color =
      ppc_inline_style.heading_color;
    document.querySelector('.pop-button').style.color =
      ppc_inline_style.link_color;
    document.querySelector('.ppc-main-text-container').style.background =
      ppc_inline_style.background_color;
    if ('400px' == ppc_inline_style.heading_max_width) {
      document
        .querySelector('.ppc-main-text-container')
        .classList.add('popup-text-squre-container');

      document
        .querySelector('.ppc-main-text-container')
        .classList.remove('popup-text-container');
    } else {
      document
        .querySelector('.ppc-main-text-container')
        .classList.remove('popup-text-squre-container');
      document
        .querySelector('.ppc-main-text-container')
        .classList.add('popup-text-container');
    }
  });
})(jQuery);
