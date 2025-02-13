jQuery(document).ready(function($) {
    function getCurrentDateTime() {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();
        var hh = String(today.getHours()).padStart(2, '0');
        var min = String(today.getMinutes()).padStart(2, '0');
        var ss = String(today.getSeconds()).padStart(2, '0');

        return yyyy + '-' + mm + '-' + dd + ' ' + hh + ':' + min + ':' + ss;
    }
    
    $('.smarty-ksf-product-search').select2({
        ajax: {
            url: smartyKsfEvents.ajaxUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
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
        minimumInputLength: 3,
        placeholder: 'Search for a product',
        allowClear: true,
        width: '100%'
    });

    $('.form-display-condition-select').select2({
        placeholder: 'Select Conditions',
        width: '100%'
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
                    <select name="smarty_klaviyo_forms[` + index + `][display_conditions][]" multiple="multiple" class="form-display-condition-select select2" style="width: 100%;">
                        <option value="out_of_stock">Out of Stock</option>
                        <option value="low_stock">Low Stock (<5)</option>
                    </select>
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

        $('.smarty-ksf-product-search').last().select2({
            ajax: {
                url: smartyKsfEvents.ajaxUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
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
            minimumInputLength: 3,
            placeholder: 'Search for a product',
            allowClear: true,
            width: '100%'
        });

        $('.form-display-condition-select').last().select2({
            placeholder: 'Select Conditions',
            width: '100%'
        });
    });

    $('.form-display-condition-select').each(function() {
        var $row = $(this).closest('tr');
    });

    $(document).on('click', '.remove-form-row', function() {
        $(this).closest('tr').remove();
    });

    // Handle tab switching
    $(".smarty-ksf-nav-tab").click(function (e) {
        e.preventDefault();
        $(".smarty-ksf-nav-tab").removeClass("smarty-ksf-nav-tab-active");
        $(this).addClass("smarty-ksf-nav-tab-active");

        $(".smarty-ksf-tab-content").removeClass("active");
        $($(this).attr("href")).addClass("active");
    });

    // Load README.md
    $("#smarty-ksf-load-readme-btn").click(function () {
        const $content = $("#smarty-ksf-readme-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyKsfEvents.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_ksf_load_readme",
                nonce: smartyKsfEvents.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading README.md</p>");
                }
            },
        });
    });

    // Load CHANGELOG.md
    $("#smarty-ksf-load-changelog-btn").click(function () {
        const $content = $("#smarty-ksf-changelog-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyKsfEvents.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_ksf_load_changelog",
                nonce: smartyKsfEvents.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading CHANGELOG.md</p>");
                }
            },
        });
    });
});
