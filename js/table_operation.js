$(document).ready(function() {
    $(document).on('click', '#add_row', function(e) {
        var tr_row = '' +
           '<tr>' +
           '<td class="slim-cell">' +
           '<div class="custom-control custom-checkbox slim-form-check">' +
           '<input type="checkbox" class="custom-control-input" name="take_user" checked="checked" id="take-user">' +
           '<label class="custom-control-label" for="take-user"></label>' +
           '</div>' +
           '</td>' +
           '<td class="slim-cell">' +
           '<div class="form-group slim-form-group">' +
           '<input type="text" class="form-control" name="handle" value="">' +
           '</div>' +
           '</td>' +
           '<td class="slim-cell">' +
           '<div class="form-group slim-form-group">' +
           '<input type="text" class="form-control" name="user_id" value="">' +
           '</div>' +
           '</td>' +
           '<td class="slim-cell">' +
           '<div class="form-group slim-form-group">' +
           '<input type="text" class="form-control" name="affiliation" value="">' +
           '</div>' +
           '</td>' +
           '</tr>'
        var row_cnt = $("table tbody").children().length;
        $(':hidden[name="row_length"]').val(parseInt(row_cnt) + 1);
        $(tr_row).appendTo($('table > tbody'));
        $('table > tbody > tr:last > td > div > input').each(function() {
            var base_name = $(this).attr('name');
            $(this).attr('name', base_name + '[' + row_cnt + ']');
        });
        $('table > tbody > tr:last > td > div > input').each(function() {
            var base_name = $(this).attr('id');
            $(this).attr('id', base_name + '-' + row_cnt);
        });
        $('table > tbody > tr:last > td > div > label').each(function() {
            var base_name = $(this).attr('for');
            $(this).attr('for', base_name + '-' + row_cnt);
        });
    });

    $(document).on('click', '#del_row', function(e) {
        var row_cnt = $("table tbody").children().length;
        if(parseInt(row_cnt) > 1) {
            $('table > tbody > tr:last').remove();
            $(':hidden[name="row_length"]').val(parseInt(row_cnt) - 1);
        }
    });

    // 全選択
    $(document).on('click', '#take_all', function(e) {
        $('input[name^=take_user]').prop('checked', true );
    });

    // 全解除
    $(document).on('click', '#remove_all', function(e) {
        $('input[name^=take_user]').prop('checked', false);
    });

    // ボタンを押したときに form action の内容を変更
    $(document).on('click', '#run_team_making_btn', function(e) {
        document.getElementById('main_form_table').action="./php/maker.php";
    });

    $(document).on('click', '#csv_export_btn', function(e) {
        document.getElementById('main_form_table').action="./php/csv_download.php";
    });
});
