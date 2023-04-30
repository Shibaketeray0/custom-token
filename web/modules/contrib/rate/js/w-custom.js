/**
 * @file
 * Modifies the Rate custom rating.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.customRating = {
    attach: function (context, settings) {
      $('body').find('.custom-rating-wrapper').each(function () {
        // If element is editable, enable submit click.
        var isEdit = $(this).attr('can-edit');
        if (isEdit === 'true') {
          $(this).find('label')
            .click(function (e) {
              $(this).find('input').prop('checked', true);
              $(this).closest('form').find('.custom-rating-submit').trigger('click');
            });
        }
        else {
          $(this).find('label').css('cursor', 'default'); // Cursor to arrow.
        }
      });
    }
  };
})(jQuery, Drupal);
