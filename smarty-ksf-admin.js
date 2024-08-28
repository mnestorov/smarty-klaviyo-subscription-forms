// Function to format product results in the Select2 dropdown
function formatProduct(product) {
    if (product.loading) {
        return product.text;
    }
    var markup = "<div class='select2-result-product clearfix'>" +
        "<div class='select2-result-product__meta'>" +
        "<div class='select2-result-product__title'>" + product.text + "</div>" +
        "</div></div>";
    return markup;
}

// Function to format the selected product
function formatProductSelection(product) {
    return product.text;
}

jQuery(document).ready(function($) {
    // Initialize Select2 for existing rows
    $('.smarty-ksf-product-select2').select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    action: 'smarty_ksf_search_products'
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 0, // Show all products when the field is clicked
        placeholder: 'Search for a product', // Placeholder text inside the field
        allowClear: true, // Add a clear option to remove selection
        width: '100%', // Ensure the Select2 field uses 100% width
        templateResult: formatProduct,
        templateSelection: formatProductSelection,
        escapeMarkup: function (markup) { return markup; } // Let Select2 handle the HTML markup
    });

    $('#smarty-add-form-row').on('click', function() {
        var index = $('#klaviyo-forms-table tbody tr').length;
        var newRow = `
            <tr>
                <td>
                    <select name="smarty_klaviyo_forms[` + index + `][product_ids][]" multiple="multiple" class="smarty-ksf-product-select2" style="width: 100%;"></select>
                </td>
                <td><input type="text" name="smarty_klaviyo_forms[` + index + `][form_id]" /></td>
                <td>
                    <label class="smarty-toggle-switch">
                        <input type="checkbox" name="smarty_klaviyo_forms[` + index + `][enabled]" value="yes">
                        <span class="slider round"></span>
                    </label>
                </td>
                <td><a href="#" target="_blank">Preview Form</a></td>
                <td><button type="button" class="button button-secondary remove-form-row">Remove</button></td>
            </tr>
        `;
        $('#klaviyo-forms-table tbody').append(newRow);

        // Initialize Select2 on the new row
        $('.smarty-ksf-product-select2').select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        action: 'smarty_ksf_search_products'
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 0, // Show all products when the field is clicked
            placeholder: 'Search for a product', // Placeholder text inside the field
            allowClear: true, // Add a clear option to remove selection
            width: '100%', // Ensure the Select2 field uses 100% width
            templateResult: formatProduct,
            templateSelection: formatProductSelection,
            escapeMarkup: function (markup) { return markup; } // Let Select2 handle the HTML markup
        });
    });

    $(document).on('click', '.remove-form-row', function() {
        $(this).closest('tr').remove();
    });
});
