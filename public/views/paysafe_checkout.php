<?php
/**
 * Created by PhpStorm.
 * User: hjl
 * Date: 2022/3/29
 * Time: 14:28
 */

$items = json_decode($centerParams['items'], true);
$filePath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'file' .DIRECTORY_SEPARATOR;
$sUrl = $items['return_url'];
$fParams = parse_url($sUrl);


file_put_contents($filePath .$centerId . '.txt', json_encode(
    [
        'f_url' => $fParams['scheme'] . '://' . $fParams['host'],
        's_url' => $sUrl,
        'html_data' => $centerParams,
    ]
));
?>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Credit Card Payment Gateway</title>
    <meta http-equiv="X-UA-Compatible" content="IE-Edge,chrome">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0, user-scalable=no, minimal-ui">
    <script type="text/javascript" src="./static/js/jq.js" charset="UTF-8"></script>
    <script src="./static/card-js.min.js"></script>
    <link media="all" rel="stylesheet" href="./static/card-js.min.css"/>
    <link rel='stylesheet' id='google-fonts-1-css' href='https://fonts.googleapis.com/css?family=Sen%3A100%2C100italic%2C200%2C200italic%2C300%2C300italic%2C400%2C400italic%2C500%2C500italic%2C600%2C600italic%2C700%2C700italic%2C800%2C800italic%2C900%2C900italic%7CNunito+Sans%3A100%2C100italic%2C200%2C200italic%2C300%2C300italic%2C400%2C400italic%2C500%2C500italic%2C600%2C600italic%2C700%2C700italic%2C800%2C800italic%2C900%2C900italic&#038;display=auto&#038;ver=6.5.2' media='all' />
    <title>Credit Card Payment Gateway</title>
    <style>
        body {font-family: 'Nunito Sans'!important;}
        .card-js .expiry-container,.card-js .cvc-container{margin-bottom:12px;position:relative;}
        .card-js .expiry-container{width:49%;margin-right:2%;}
        .card-js .cvc-container{width:49%;float:none;}
        .card-js input{
            width:100%;padding: 15px 0 15px 38px;
            color:#504391;
            box-sizing: border-box;
            position: relative;display:block;
            font-size:13px;
            font-weight: 400;
            letter-spacing: 1;
            background: #FBF8F0;
            border-radius: 24px;
            border:1px solid #005C4C;
            font-family: 'Nunito Sans'!important;
        }
        .card-js input::-webkit-input-placeholder{
            color: #999;
        }

        .card-js input:focus{
            border:1px solid #754FE6;
            color:#504391;
        }

        .card-js input, .card-js select{height:auto;}

        .card-js .expiry-wrapper{margin-right:0}
        .card-js .cvc-wrapper{margin-left:0}
        .card-js .icon{top:38px;right:10px;left:auto!important;display:none;}
        .card-js .cvc-wrapper .icon{top:10px;}
        .card-js .card-number-wrapper {margin-bottom:30px;position:relative;}
        .card-js .card-number-wrapper .card-type-icon {z-index:99;top: 40px;height: 33px;    background: url(/wp-content/uploads/2025/03/checkout-pay1.png);
            width: 82px;
            opacity: 1;
            background-position: right center !important;background-size: contain;}
        .card-js input.card-number, .card-js input.cvc, .card-js input.name,.card-js .expiry-wrapper .expiry {padding-left: 10px;}

        .panel {
            padding: 30px 0;
            display: block;
            max-height:450px;
            width: 1200px;
            margin: 0 auto 0;
            position: relative;
            background: #FBF8F0;
            border-radius: 20px;
            color: #000!important;

        }
        .bg {
            width: 422px;
            margin: 20px auto 0;
            border-radius: 25px;
            background: #fff;
        }
        .text-center {
            text-align: center;
            align-items: center;
        }
        .bg-box {padding:38px 38px 0}
        .btn {
            color: #fff;
            margin: 20px auto 20px;
            display: inline-flex;
            font-weight: 500;
            cursor: pointer;
            padding: 12px 16px;
            font-size: 16px;
            border: 0;
            width: 100%;
            background: #005C4C;
            border-radius: 24px;
            align-items: center;
            justify-content: center;
            gap:10px;
        }
        #pay_button:hover {

            filter: none;
        }
        #pay_button:active {
            box-shadow: inset 1px 1px 2px #babecc, inset -1px -1px 2px #fff;
            filter: none;
            transform: none;
        }
        .button-disabled {
            background:rgb(120,125,128);
            color: #000;
            display: block;
            width: 100%;
            border: 1px solid rgba(46, 86, 153, 0.0980392);
            border-bottom-color: rgba(46, 86, 153, 0.4);
            border-top: 0;
            border-radius: 4px;
            font-size: 17px;
            text-shadow: rgba(46, 86, 153, 0.298039) 0px -1px 0px;
            line-height: 34px;
            -webkit-font-smoothing: antialiased;
            font-weight: bold;
            margin-top: 20px;
        }
        .btn:hover {
            cursor: pointer;
        }
        .panel .centerImg img{
            width:180px;
            height:auto;
        }
        .panel .panelImg{
            width: 100%;
            margin: 14px auto 30px;display: flex;max-width:100%;
        }
        .panel .panelImg img{
            height:30px;
            float:right;
        }
        .panel .loading img{
            width: 4rem;
            height: 4rem;
            margin-top: -95%;
            margin-left: -9%;
            z-index: 1000;
            position: fixed;
            display: none;
        }
        #my-card .name {
            font-family: Nunito Sans;
            font-weight: 400;
            font-size: 16px;
            color:#005C4C;
            margin-bottom:10px;
            text-align: left;
        }
        #my-card{
            position: relative;
        }
        #my-card .icon .svg{fill: #000;}

        .title1 {
            font-family: Nunito Sans;
            font-weight: 600;
            font-size: 24px;
            text-align: center;
            color:#005C4C;
        }
        .title2 {
            font-family: Nunito Sans;
            font-weight: 300;
            font-size: 12px;
            line-height: 25px;
            text-align: center;
            color:#005C4C;

        }
        .title3 {
            width: 50%;
        }
        .info {height:40px;line-height:20px;padding:0 0 0 20px;margin:10px 0;background:url(/wp-content/uploads/2024/11/padlock-1.png) no-repeat left center;font-family: 'Nunito Sans';
            font-size: 12px;
            font-weight: 300;
            text-align: left;
            color:#666;
        }
        .info span {
            font-family: 'Nunito Sans';
            font-size: 14px;
            font-weight: 400;
            text-align: left;
            color:#000;
        }


        /*.card-js .name.active {*/
        /*    transform: translateY(-80%);*/
        /*    opacity: 1;*/
        /*}*/


        @media (max-width: 1024px){
            .panel{
                width: 70%;
                box-sizing: border-box;
            }
        }
        @media (max-width: 800px){
            .card-js .expiry-container{width:100%;margin-right:0%;}
            .card-js .cvc-container{width:100%;float:none;}
            .btn {margin:0 auto;}
            .panel .panelImg{
                margin:0 auto 10px;
            }
            .title1 {
                font-size:20px;
            }
            .text-center {
                overflow: hidden;
                width: 100%;
            }
            .panel{
                max-height: none;
                padding: 20px 15px;
                width: calc(100% - 30px) !important;
                margin:0 15px;
            }
            .bg {
                width: 100%;
            }
            .bg-box {padding:20px 15px}

        }
        @media (max-width: 540px){
            .panel{
                width: 100%;
                box-sizing: border-box;
            }
        }
        @media (max-width: 420px){
            .panel{
                width: 90%;
                box-sizing: border-box;
            }

        }
        @media (max-width: 380px){
            .panel{
                width: 100%;
                box-sizing: border-box;
            }
            @media (max-height: 670px){
                .panel{
                }
            }

        }
        @media (max-width: 320px){
            .panel{
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body marginwidth="0" marginheight="0">

<div class="text-center">
    <div class="panel">
        <div class="load-container load2 bbox_a">
            <div class="loader"></div>
        </div>
        <div class="title1">Safe and Secure Payment Online</div>
        <!--<div class="title2">Safe and Secure Payment Online</div>-->

        <div class="bg">
            <div class="bg-box">
                <div class="card-js" id="my-card">
                    <input class="name" id="cardholder" name="card-holders-name" placeholder="Name on card">
                    <input class="card-number my-custom-class" name="card-number" id="card_number">
                    <input class="expiry-month" name="expiry_month" id="expiry_month">
                    <input class="expiry-year" name="expiry_year" id="expiry_year">
                    <input class="cvc" name="cvc" id="cvc">
                </div>
                <button class="btn" id="pay_button"><svg width="19" height="24" viewBox="0 0 19 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.4297 17.0863C17.7317 17.127 18.0408 17.1278 18.3494 17.0863V20.2498C18.3494 21.1039 17.8918 21.8924 17.1503 22.3161L15.1357 23.4673C15.0625 23.5091 14.9727 23.5091 14.8995 23.4673L12.8849 22.3161C12.1434 21.8924 11.6858 21.1039 11.6858 20.2498V17.0863C11.9944 17.1278 12.3035 17.127 12.6055 17.0863C13.3323 16.9883 14.0176 16.6593 14.5534 16.1353L14.9381 15.759C14.9823 15.7158 15.0529 15.7158 15.0971 15.759L15.4818 16.1353C16.0176 16.6593 16.7029 16.9883 17.4297 17.0863ZM12.6377 18.0414C13.5116 17.9429 14.3428 17.5835 15.0176 17.001C15.6924 17.5835 16.5237 17.9429 17.3975 18.0414V20.2498C17.3975 20.7622 17.1229 21.2354 16.678 21.4896L15.0176 22.4384L13.3572 21.4896C12.9123 21.2354 12.6377 20.7622 12.6377 20.2498V18.0414Z" fill="#EDECE3"></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M13.6398 19.3431C13.8257 19.1572 14.127 19.1572 14.3129 19.3431L14.8167 19.8469L15.9931 18.6705C16.179 18.4846 16.4804 18.4846 16.6663 18.6705C16.8521 18.8564 16.8521 19.1577 16.6663 19.3436L14.8167 21.1932L13.6398 20.0163C13.4539 19.8304 13.4539 19.529 13.6398 19.3431Z" fill="#EDECE3"></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M0.738281 11.1533C0.738281 9.83894 1.80379 8.77344 3.11815 8.77344H16.4454C17.7598 8.77344 18.8253 9.83894 18.8253 11.1533V15.7941C18.8253 16.0569 18.6122 16.27 18.3493 16.27C18.0864 16.27 17.8733 16.0569 17.8733 15.7941V11.1533C17.8733 10.3647 17.234 9.72539 16.4454 9.72539H3.11815C2.32953 9.72539 1.69023 10.3647 1.69023 11.1533V19.7208C1.69023 20.5095 2.32953 21.1488 3.11815 21.1488H10.7337C10.9966 21.1488 11.2097 21.3619 11.2097 21.6247C11.2097 21.8876 10.9966 22.1007 10.7337 22.1007H3.11815C1.80379 22.1007 0.738281 21.0352 0.738281 19.7208V11.1533Z" fill="#EDECE3"></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M9.54349 14.9646C10.2007 14.9646 10.7334 14.4319 10.7334 13.7747C10.7334 13.1175 10.2007 12.5848 9.54349 12.5848C8.88631 12.5848 8.35356 13.1175 8.35356 13.7747C8.35356 14.4319 8.88631 14.9646 9.54349 14.9646ZM9.54349 15.9166C10.7264 15.9166 11.6854 14.9576 11.6854 13.7747C11.6854 12.5918 10.7264 11.6328 9.54349 11.6328C8.36056 11.6328 7.40161 12.5918 7.40161 13.7747C7.40161 14.9576 8.36056 15.9166 9.54349 15.9166Z" fill="#EDECE3"></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M9.06832 18.5313L9.06832 15.4375L10.0203 15.4375L10.0203 18.5313C10.0203 18.7942 9.80716 19.0073 9.54429 19.0073C9.28142 19.0073 9.06832 18.7942 9.06832 18.5313Z" fill="#EDECE3"></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.83179 6.75256C3.83179 3.53236 6.44227 0.921875 9.66247 0.921875C12.8827 0.921875 15.4932 3.53236 15.4932 6.75256V9.25142H14.5412V6.75256C14.5412 4.05811 12.3569 1.87382 9.66247 1.87382C6.96802 1.87382 4.78374 4.05811 4.78374 6.75256V9.25142H3.83179V6.75256Z" fill="#EDECE3"></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.97461 6.99349C5.97461 4.95622 7.62614 3.30469 9.66341 3.30469C11.7007 3.30469 13.3522 4.95622 13.3522 6.99349V9.01638H12.4003V6.99349C12.4003 5.48197 11.1749 4.25664 9.66341 4.25664C8.15189 4.25664 6.92656 5.48197 6.92656 6.99349V9.01638H5.97461V6.99349Z" fill="#EDECE3"></path>
                    </svg>Pay Securely Now</button>
                <span style="display: block;color: red;height: 0;line-height:24px;transition: all 0.3s;overflow: hidden;" id="errorText"></span>
            </div>
        </div>
    </div>

</div>

<style>
    .load-container {
        width: 100%;
        height: 100%;
        position: absolute;
        margin: auto;
        left: 0;
        top: 0;
        z-index: 10;
        display:none;
        background: #ffffffad;
    }

    .load-container .loader {
        color: #39c3ec;
        font-size: 8px;
        margin: auto;
        width: 1em;
        height: 1em;
        top: 50%;
        transform: translateY(-50%);
        border-radius: 50%;
        position: relative;
        border: 1px solid #fff;
        box-shadow: 0 -2.6em 0 0.2em, 1.8em -1.8em 0 0.2em, 2.5em 0em 0 0.2em, 1.75em 1.75em 0 0.2em, 0em 2.5em 0 0.2em, -1.8em 1.8em 0 0.2em, -2.6em 0em 0 0.2em, -1.8em -1.8em 0 0.2em;
    }

    @keyframes aniLoad2 {
        0%, 100% {
            box-shadow: 0 -3em 0 0.2em, 2em -2em 0 0em, 3em 0 0 -1em, 2em 2em 0 -1em, 0 3em 0 -1em, -2em 2em 0 -1em, -3em 0 0 -1em, -2em -2em 0 0;
        }
        12.5% {
            box-shadow: 0 -3em 0 0, 2em -2em 0 0.2em, 3em 0 0 0, 2em 2em 0 -1em, 0 3em 0 -1em, -2em 2em 0 -1em, -3em 0 0 -1em, -2em -2em 0 -1em;
        }
        25% {
            box-shadow: 0 -3em 0 -1em, 2em -2em 0 0, 3em 0 0 0.2em, 2em 2em 0 0, 0 3em 0 -1em, -2em 2em 0 -1em, -3em 0 0 -1em, -2em -2em 0 -1em;
        }
        37.5% {
            box-shadow: 0 -3em 0 -1em, 2em -2em 0 -1em, 3em 0 0 0, 2em 2em 0 0.2em, 0 3em 0 0, -2em 2em 0 -1em, -3em 0 0 -1em, -2em -2em 0 -1em;
        }
        50% {
            box-shadow: 0 -3em 0 -1em, 2em -2em 0 -1em, 3em 0 0 -1em, 2em 2em 0 0, 0 3em 0 0.2em, -2em 2em 0 0, -3em 0 0 -1em, -2em -2em 0 -1em;
        }
        62.5% {
            box-shadow: 0 -3em 0 -1em, 2em -2em 0 -1em, 3em 0 0 -1em, 2em 2em 0 -1em, 0 3em 0 0, -2em 2em 0 0.2em, -3em 0 0 0, -2em -2em 0 -1em;
        }
        75% {
            box-shadow: 0 -3em 0 -1em, 2em -2em 0 -1em, 3em 0 0 -1em, 2em 2em 0 -1em, 0 3em 0 -1em, -2em 2em 0 0, -3em 0 0 0.2em, -2em -2em 0 0;
        }
        87.5% {
            box-shadow: 0 -3em 0 0, 2em -2em 0 -1em, 3em 0 0 -1em, 2em 2em 0 -1em, 0 3em 0 -1em, -2em 2em 0 -1em, -3em 0 0 0, -2em -2em 0 0.2em;
        }
    }

    .load2 .loader {
        animation: aniLoad2 1.3s infinite linear;
    }
</style>
<script>
    window.parent.postMessage("loadinged", "*");
    var $ = jQuery;
    $(document).ready(function () {
        $("#pay_button").click(function (event) {
            showErrorMsg('',true);
            var myCard = $('#my-card');
            var cardNumber = myCard.CardJs('cardNumber');
            var expiryMonth = myCard.CardJs('expiryMonth');
            var expiryYear = myCard.CardJs('expiryYear');
            var cvc = myCard.CardJs('cvc');
            var cardStart = cardNumber[0];
            if (!check_card(cardNumber)) {
                showErrorMsg('Please input correct card number!');
                return false;
            } else if (expiryMonth.length === 0 || expiryYear.length === 0) {
                showErrorMsg('Please input correct card expiry month and year!');
                return false;
            } else if (cvc.length === 0) {
                showErrorMsg('Please input correct cvc!');
                return false;
            }
            if (!CardJs.isExpiryValid(expiryMonth, expiryYear)) {
                showErrorMsg('Expired Card.');
                return false;
            }
            $("#pay_button").prop('disabled', true).removeClass('btn').addClass('button-disabled');
            $(".load-container.load2").show();
            handleResponse();
            return false;

        });
        $('#card_number').on('focus', function() {
            if($(this).val() == ''){
                $(".card-type-icon").removeClass("visa");
                $(".card-type-icon").removeClass("master-card");
            }
            $(".card-number-wrapper .name").addClass('active');
        });

        $('#card_number').on('blur', function() {
            if($(this).val() == ''){
                $(".card-type-icon").removeClass("visa");
                $(".card-type-icon").removeClass("master-card");
            }
            $(".card-number-wrapper .name").removeClass('active');
        });

        $('.expiry').on('focus', function() {
            $(".expiry-container .name").addClass('active');
        });

        $('.expiry').on('blur', function() {
            $(".expiry-container .name").removeClass('active');
        });

        $('#cvc').on('focus', function() {
            $(".cvc-container .name").addClass('active');
        });

        $('#cvc').on('blur', function() {
            $(".cvc-container .name").removeClass('active');
        });
    });

    function handleResponse(status, response) {
        $("#pay_button").prop('disabled', false).addClass('btn').removeClass('button-disabled');
        $.ajax({
            url:'./pay/createOrder',
            method:'POST',
            dataType:'json',
            data:{
                amount:<?=$centerParams['amount'];?>,
                order_no:"<?=$centerParams['order_no'];?>",
                currency:"<?=$centerParams['currency'];?>",
                center_id:"<?=$centerId;?>",
                email:"<?=$centerParams['email'];?>",
                phone:"<?=$centerParams['telephone'];?>",
                country:"<?=$centerParams['country'];?>",
                currency_code:"<?=$centerParams['currency'];?>",
                city:"<?=$centerParams['city'];?>",
                state:"<?=$centerParams['state'];?>",
                address1:"<?=$centerParams['address'];?>",
                address2:"",
                zip:"<?=$centerParams['zip_code'];?>",
                first_name:"<?=$centerParams['first_name'];?>",
                last_name:"<?=$centerParams['last_name'];?>",
                client_ip:"<?=get_client_ip();?>",
                card_holder:$("#cardholder").val(),
                card_number:$("#card_number").val(),
                expiry_year:$("#expiry_year").val(),
                expiry_month:$("#expiry_month").val(),
                cvc:$("#cvc").val(),
                colorDepthBits: screen.colorDepth,
                javaEnabled: navigator.javaEnabled(),
                language: navigator.language,
                screenHeight: screen.height,
                screenWidth: screen.width,
                timezoneOffset: new Date().getTimezoneOffset(),
                token:"<?= get_params_token($centerParams['first_name'],$centerId,$centerParams['amount'],$centerParams['last_name']);?>"
            },
            beforeSend(){

            },
            success(res){
                $(".load-container.load2").hide()
                console.log(res);
                if (res.errcode === 0)
                {
                    if (res.data.success_risky)
                    {
                        $(".load-container.load2").show();
                        window.parent.postMessage("success_risky","*");
                        return false;
                    }
                    if (res.data.url)
                    {
                        showErrorMsg('Jumping to 3ds Authorization page now...');
                        $("#errorText").css({
                            'height':'auto',
                            'color':'green'
                        });
                        $(".load-container.load2").show();
                        window.parent.postMessage(res.data.url,"*");
                        return false;
                    }
                    window.parent.postMessage("succeeded","*");
                    showErrorMsg('Payment complete! Redirecting to your order confirmation...');
                    $("#errorText").css({
                        'height':'auto',
                        'color':'green'
                    });
                    $(".load-container.load2").show();
                }else{
                    // error count
                    sendFailedCount();
                    showErrorMsg(res.errmsg);
                }
            },
            complete(){
            },
            error(res){
                $(".load-container.load2").hide()
                sendFailedCount();
                showErrorMsg(res.errmsg);
            }
        });
    }
    // v2 end

    function showErrorMsg(msg = '',is_empty = false)
    {
        $(".load-container.load2").hide();
        let height = is_empty ? '0px' : 'auto';
        $("#errorText").text(msg).css("height",height);
    }
    function sendErrMessage(message) {
        $.ajax({
            type: "post",
            url: './pay/keyError',
            dataType: "json",
            data:{
                center_id:<?=$_POST['center_id'] ?? 0;?>,
                reason:message
            },
            success: function (data) {
                if (data.errcode === 1)
                {
                    console.log('set error failed!');
                }
            },
            error: function (data) {
                console.log('set error failed');
                return false;
            }
        });
    }

    var error_count = 0;
    function sendFailedCount() {
        error_count ++;
        if (error_count >= 2)
        {
            $.ajax({
                type: "post",
                url: './pay/errorCount',
                dataType: "json",
                data:{center_id:<?=$_POST['center_id'] ?? 0;?>},
                success: function (data) {
                    if (data.errcode === 1)
                    {
                        console.log('set error failed!');
                    }
                    window.parent.postMessage("risky", "*");
                },
                error: function (data) {
                    console.log('set error failed');
                    return false;
                }
            });
        }
    }
</script>
</body>
</html>
