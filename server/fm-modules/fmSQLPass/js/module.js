$(document).ready(function() {
	
	$('#set_sql_password').click(function() {
		if ($('#verbose').is(":checked") == false) {
			$('#response').html('<p>Processing...please wait.</p>');
			$('#response')
				.css('opacity', 0)
				.slideDown(400, function() {
					$('#response').animate(
						{ opacity: 1 },
						{ queue: false, duration: 200 }
					);
				});
		} else {
			$('#manage_item').fadeIn(200);
			$('#manage_item_contents').fadeIn(200);
			$('#manage_item_contents').html('<h2>Password Change Results</h2><p>Processing...please wait.</p>');
		}
		
		$.ajax({
			type: "POST",
			url: 'fm-modules/fmSQLPass/ajax/processPost.php',
			data: $('#manage').serialize(),
			success: function(response)
			{
				if ($('#verbose').is(":checked") == false) {
					$('#response').html(response);
					$('#response').delay(3000).fadeTo(200, 0.00, function() {
						$('#response').slideUp(400);
					});
				} else {
					$('#manage_item_contents').html(response);
				}
			}
		});
		
		return false;
	});
	
});

function toggle(source) {
	checkboxes = document.getElementsByName('group[]');
	for(var i in checkboxes)
		checkboxes[i].checked = source.checked;
}
