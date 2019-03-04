function escapeHTML(s) {
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

$(document).ready(function() {
    // ユーザー情報を入力する表に対して、末尾に行を追加
    $(document).on('click', '#add_row', function(e) {
        var tr_row = '' +
            '<tr>' +
            '<td class="slim-cell">' +
            '<div class="custom-control custom-checkbox slim-form-check">' +
            '<input type="checkbox" class="custom-control-input" name="take_user" checked="checked" id="take-user">' +
            '<label class="custom-control-label" for="take-user"></label>' +
            '</div>' +
            '</td>' +
            '<td class="slim-cell col-sm-1">' +
            '<div class="form-group slim-form-group">' +
            '<input type="text" class="form-control" name="team_id" value="">' +
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
            '<input type="text" class="form-control" name="affiliation" id="affiliation-user" value="">' +
            '</div>' +
            '</td>' +
            '</tr>'
        var row_cnt = $("table tbody").children().length;
        $(':hidden[name="row_length"]').val(parseInt(row_cnt) + 1);
        $(tr_row).appendTo($('table > tbody'));
        $('table > tbody > tr:last > td > div > input').each(function() {
            var base_name = $(this).attr('name');
            if(base_name !== undefined) {
                $(this).attr('name', base_name + '[' + row_cnt + ']');
            }
        });
        $('table > tbody > tr:last > td > div > input').each(function() {
            var base_name = $(this).attr('id');
            if(base_name !== undefined) {
                $(this).attr('id', base_name + '-' + row_cnt);
            }
        });
        $('table > tbody > tr:last > td > div > label').each(function() {
            var base_name = $(this).attr('for');
            if(base_name !== undefined) {
                $(this).attr('for', base_name + '-' + row_cnt);
            }
        });
    });

    // ユーザー情報を入力する表に対して、末尾の行を削除
    // 1 行しかないなら何もしない
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

    // 特定の所属に関して全チェック
    $(document).on('click', '#check_by_affil', function(e) {
        var target_affil = escapeHTML($('#target_affil').val()).toLowerCase();
        var row_cnt = $("table tbody").children().length;
        for(var i=0; i<row_cnt; i++) {
            var affil_id = '#affiliation-user-' + i;
            var check_id = '#take-user-' + i;
            var affil = escapeHTML($(affil_id).val()).toLowerCase();
            if(target_affil === affil) {
                $(check_id).prop('checked', true);
            }
        }
    });

    $(document).on('click', '#uncheck_by_affil', function(e) {
        var target_affil = escapeHTML($('#target_affil').val()).toLowerCase();
        var row_cnt = $("table tbody").children().length;
        for(var i=0; i<row_cnt; i++) {
            var affil_id = '#affiliation-user-' + i;
            var check_id = '#take-user-' + i;
            var affil = escapeHTML($(affil_id).val()).toLowerCase();
            if(target_affil === affil) {
                $(check_id).prop('checked', false);
            }
        }
    });

    /*
    // ひとつのフォームから複数のファイルを受け取れるので不要になった

    // json ファイルインポートリストに対して、末尾に行を追加
    $(document).on('click', '#add_row_json', function(e) {
        var row_cnt = parseInt($('#json_import_section').children().length);
        var tr_row = '' +
            '<div class="input-group">' +
            '<label class="input-group-btn">' +
            '<span class="btn btn-primary">' +
            'ファイルを選択<input type="file" name="json_file[]" accept="application/json" style="display:none">' +
            '</span>' +
            '</label>' +
            '<input type="text" class="form-control" readonly="">' +
            '</div>';
        $(tr_row).appendTo($('#json_import_section'));
    });

    // json ファイルインポートリストに対して、末尾の行を削除
    // 1 行しかない場合は何もしない
    $(document).on('click', '#del_row_json', function(e) {
        var row_cnt = parseInt($('#json_import_section').children().length);
        if(row_cnt > 1) {
            $('#json_import_section > div:last').remove();
        }
    });
    */

    // ボタンを押したときに form action の内容を変更
    $(document).on('click', '#run_team_making_btn', function(e) {
        document.getElementById('main_form_table').action="./php/maker.php";
    });

    $(document).on('click', '#csv_export_btn', function(e) {
        document.getElementById('main_form_table').action="./php/csv_download.php";
    });
});
