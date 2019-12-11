<style>
    .alert {
        background-image: none;
        box-shadow: none;
        text-shadow: none;
        padding: 9px 15px 9px 19px;
        border-radius: 3px;
        font-size: 13px;
        border-width: 0;
        -webkit-transition: all 0.2s linear 0s;
        transition: all 0.2s linear 0s;
        text-align: center;
    }
    .alert-danger {
        background-color: #fddddd;
        color: #933432;
        border-color: #933432;
    }
    .alert-success {
        background-color: #cff5f2;
        color: #0a7c71;
        border-color: #0a7c71;
    }
</style>
<!-- START Errors -->
@if ($errors->any())
    <div class="alert alert-danger">
    <ul>
    @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
    @endforeach
    </ul>
</div>
@endif
<!-- END Errors -->
@if(Session::has('flash_message'))
    <div class="alert alert-success">
        {{ Session::get('flash_message') }}
    </div>
@endif
@if(Session::has('flash_error'))
    <div class="alert alert-danger">
        {{ Session::get('flash_error') }}
    </div>
@endif
