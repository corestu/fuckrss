(function () {
	var body = document.getElementById('fuckrss-rules-body');
	var addButton = document.getElementById('fuckrss-add-rule');
	var template = document.getElementById('fuckrss-rule-tpl');

	if (!body || !addButton || !template) {
		return;
	}

	var seq = 0;

	addButton.addEventListener('click', function () {
		var holder = document.createElement('tbody');
		holder.innerHTML = template.textContent.replace(/__i__/g, 'n' + Date.now() + '_' + seq++);
		body.appendChild(holder.firstElementChild);
	});

	body.addEventListener('click', function (event) {
		var button = event.target.closest('.fuckrss-remove-rule');
		if (!button) {
			return;
		}

		var row = button.closest('tr');
		if (!row) {
			return;
		}

		if (body.querySelectorAll('tr.fuckrss-rule').length <= 1) {
			row.querySelectorAll('input, textarea').forEach(function (field) {
				field.value = '';
			});
			return;
		}

		row.remove();
	});
})();
