<!doctype html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Scorebee Email Template</title>
    <meta name="description" content="Reset Password Email Template.">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style type="text/css">
        a:hover {text-decoration: underline !important;}
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #F0F0F0;" leftmargin="0">
    <!--100% body table-->
    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#F0F0F0">
        <tr>
            <td>
                <table style="background-color: #F0F0F0; max-width:670px;  margin:0 auto;" width="100%" border="0"
                    align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">
                          <a href="" title="logo" target="_blank">
                            <img width="150" src="http://mobileapi.scorebee.com/scorebee-logo-new.png" title="logo" alt="logo">
                          </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                                style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06); padding: 0 20px;">
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>
                                        <h2 style="font-size: 20px; font-family: Roboto; font-weight: 500; text-align: left; margin: 5px 0;">Hi {{$fullName}}</h2>
                                        <p style="font-size: 16px; font-family: Roboto; font-weight: 400; color: #9E9EA7; text-align: left; margin: 5px 0;">Here are your password reset instructions.</p>
                                    </td>
                                    <td>
                                        <img @if($avatar) src="{{ $avatar }}" @else src='http://mobileapi.scorebee.com/avatar.png' @endif alt="Profile Image" width="80px" style="border-radius: 50%;">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height:20px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="background: #F0F0F0; height: 2px; width: 100%;">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <p style="text-align: left; padding-right: 10px; font-family: Roboto; font-size: 16px; line-height: 1.4; color: #6a6a6a;">Please use the below code to reset your password.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <p style="display: inline-block; width: fit-content; background: #feda16; padding: 15px 30px;border-radius: 6px;font-family: Roboto;font-size: 18px; font-weight:500;">{{ $code }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p style="text-align: left; font-family: Roboto; font-size: 16px; line-height: 1.4; color: #6a6a6a;">Thank you,<br> Team Scorebee</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height:20px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <!-- <p style="text-align: left; padding-right: 10px; font-family: Roboto; font-size: 14px; line-height: 1.4; color: #a1a1a1;">Lorem ipsum dolor sit amet consectetur adipisicing elit. Fugiat libero officia dolor doloremque molestias. Illo optio minus eaque dolor non. <a href="javascript:void(0);" style="color: #000000; font-family: Roboto; font-weight: 500; text-decoration: none;">Help Center</a> </p> -->
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <!-- <tr>
                        <td style="text-align:center;" colspan="2">
                            <p style="text-align: center;font-family: Roboto;">Scorebee LTD.</p>
                            <ul style="display: inline-block; padding: 0;">
                                <li style="display: inline; color: rgb(113, 113, 113); font-family: Roboto; font-weight: 500;">524 Yates</li>
                                <li style="display: inline; color: rgb(113, 113, 113); font-family: Roboto; font-weight: 500;">Victoria, BC V8W 1K8, Canada</li>
                            </ul>
                        </td>
                    </tr> -->
                    <tr>
                        <td colspan="2" style="text-align: center;">
                            <img src="http://mobileapi.scorebee.com/scorebee-logo.png" alt="Logo" width="100" style="border-radius: 50%;">
                        </td>
                    </tr>
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <!--/100% body table-->
</body>

</html>
