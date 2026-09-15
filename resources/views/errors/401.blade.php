@php
    $errorCode = '401';
    $errorTitle = '未授权';
    $errorMessage = '您需要登录后才能访问此页面。';
    $errorIcon = 'ti ti-lock-access';
    $errorCssFile = 'errors-401';
    $errorAction = '<a href="'.route('login').'" class="error-link"><i class="ti ti-login me-2"></i>去登录</a> <a href="javascript:history.back()" class="error-link"><i class="ti ti-arrow-left me-2"></i>返回上一页</a>';
@endphp
@extends('layouts.error')
