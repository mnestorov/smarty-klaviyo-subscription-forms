jQuery(document).ready(function($) {
    // Function to get the current date in the format yyyy-mm-dd
    function getCurrentDateTime() {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
        var yyyy = today.getFullYear();
        var hh = String(today.getHours()).padStart(2, '0');
        var min = String(today.getMinutes()).padStart(2, '0');
        var ss = String(today.getSeconds()).padStart(2, '0');

        return yyyy + '-' + mm + '-' + dd + ' ' + hh + ':' + min + ':' + ss;
    }

    $('.smarty-ksf-product-search').select2({
        ajax: {
            url: ajaxurl, // WordPress AJAX
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    action: 'smarty_ksf_search_products' // WordPress AJAX action
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 3,
        placeholder: 'Search for a product',
        allowClear: true, // Add a clear option to remove selection
        width: '100%',
    });

    $('#smarty-add-form-row').on('click', function() {
        var index = $('#smarty-klaviyo-forms-table tbody tr').length;
        var createdOnDate = getCurrentDateTime();
        var newRow = `
            <tr>
                <td>
                    <select name="smarty_klaviyo_forms[` + index + `][product_ids][]" multiple="multiple" class="smarty-ksf-product-search" style="width: 100%;"></select>
                </td>
                <td>
                    <input type="text" name="smarty_klaviyo_forms[` + index + `][form_id]" />
                </td>
                <td>
                    <select name="smarty_klaviyo_forms[` + index + `][hook]" class="form-hook-select">
                        <option value="woocommerce_before_main_content">Before Main Content</option>
                        <option value="woocommerce_before_single_product">Before Single Product</option>
                        <option value="woocommerce_single_product_summary">Single Product Summary</option>
                        <option value="woocommerce_after_single_product_summary">After Single Product Summary</option>
                        <option value="woocommerce_after_single_product">After Single Product</option>
                        <!-- Add more hooks here if needed -->
                    </select>
                </td>
                <td>
                    <label class="smarty-toggle-switch">
                        <input type="checkbox" name="smarty_klaviyo_forms[` + index + `][enabled]" value="yes">
                        <span class="slider round"></span>
                    </label>
                </td>
                <td style="position: relative;">
                    <span>` + createdOnDate + `</span>
                    <button type="button" class="remove-form-row">X</button>
                </td>
            </tr>
        `;
        $('#smarty-klaviyo-forms-table tbody').append(newRow);

        // Initialize Select2 on the new row
        $('.smarty-ksf-product-search').select2({
            ajax: {
                url: ajaxurl, // WordPress AJAX
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        action: 'smarty_ksf_search_products' // WordPress AJAX action
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 3,
            placeholder: 'Search for a product',
            allowClear: true, // Add a clear option to remove selection
            width: '100%',
        });

        // Remove form row
        $(document).on('click', '.remove-form-row', function() {
            $(this).closest('tr').remove();
        });
    });

    $(document).on('click', '.remove-form-row', function() {
        $(this).closest('tr').remove();
    });
});
