$(document).ready(function () {
    $(document).on('change', '#blockcategoris', function() {
        var val = $(this).val();
        if(!val)
            return false;
        location.href = val;
    })
});