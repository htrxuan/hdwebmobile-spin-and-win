(function ($) {
	'use strict';

	function toggleTriggerFields() {
		var mode = $('input[name="hdspin_options[trigger_mode]"]:checked').val();
		$('.hdspin-field-delay').closest('input').prop('disabled', mode !== 'delay');
	}

	function toggleTargetingFields() {
		var mode = $('input[name="hdspin_options[targeting_mode]"]:checked').val();
		$('.hdspin-field-page-ids').toggle(mode === 'specific');
	}

	$(document).on('change', 'input[name="hdspin_options[trigger_mode]"]', toggleTriggerFields);
	$(document).on('change', 'input[name="hdspin_options[targeting_mode]"]', toggleTargetingFields);

	$(function () {
		toggleTriggerFields();
		toggleTargetingFields();
	});

})(jQuery);
