var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
function setKnown(id) {
    showLoader();
    $.ajax({
        url: '/set-as-known',
        type: 'POST',
        data: {
            _token:     CSRF_TOKEN,
            id:         id
        },
        dataType: 'JSON',
        success: function (data) { 
            $('.known-' + id + ' span').removeClass('yes').removeClass('no').addClass(data.status);
            $('#row-' + id).slideUp('slow');
            $('#known-count').html(data.known); 
            hideLoader();  
        }   
    }); 
}    

function translateWords() {
    setTimeout('', 5000);
    $.ajax({ 
        url: '/translate',
        type: 'POST',
        data: {
            _token:     CSRF_TOKEN,
        },
        dataType: 'JSON',
        success: function (data) { 
        }
    }); 
}

function deleteWord(id) {
    showLoader();
    $.ajax({
        url: '/delete-word',
        type: 'POST',
        data: {
            _token:     CSRF_TOKEN,
            id:         id
        },
        dataType: 'JSON',
        success: function (data) { 
            $('#row-' + id).slideUp('slow');
            hideLoader();
        }
    }); 
}    

function changeTranslation(elem, id) {
    var val = $(elem).val();
    $.ajax({
        url: '/change-translation',
        type: 'POST',
        data: {
            _token: CSRF_TOKEN,
            id:     id,
            value:  val
        },
        dataType: 'JSON',
        success: function (data) { 
        }
    }); 
}

function showLoader() {
    $('.loader').show();
} 

function hideLoader() {
    $('.loader').hide();
}