$(document).ready(function() {
    /*
    function canUseFileReader() {
        // メソッド非対応
        if(!window.File) {
            $('#warning_csv').val("File クラスに対応していません。");
            return false;
        }
        if(!window.FileReader) {
            $('#warning_csv').val("FileReader クラスに対応していません。");
            return false;
        }
        return true;
    }

    canUseFileReader();
    (function() {
        var element_file = document.getElementById("browse_csv");
        if(canUseFileReader() == false) return;

        element_file.addEventListener("change", function(e) {
            if(!(element_file.value)) return;

            var file_list = element_file.files;
            if(!file_list) return;

            var file = file_list[0];
            if(!file) return;

            console.log(file_list);
        });
    })();
    */
    
    // ファイルブラウザ画面にファイル名を表示
    $(document).on('change', 'input[type="file"]', function() {
        function r(str) {
            return str.replace(/\\/g, '/').replace(/.*\//, '');
        }
        
        var input = $(this),
            numFiles = input.get(0).files ? input.get(0).files.length : 0;
        
        var label = '';
        for(var i=0; i<numFiles; i++) {
            label += r(input.get(0).files[i].name);
            if(i + 1 < numFiles) label += ', ';
        }

        input.parent().parent().next('input[type="text"]').val(label);
    });
});
