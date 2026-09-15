@php
    $errorCode = '429';
    $errorTitle = '请求过于频繁';
    $errorMessage = '您的操作过于频繁，请稍作休息后再试。';
    $errorIcon = 'ti ti-clock-pause';
    $errorCssFile = 'errors-429';
    $errorAction = '<a href="javascript:history.back()" class="error-link"><i class="ti ti-arrow-left me-2"></i>返回上一页</a> <a href="/" class="error-link"><i class="ti ti-home me-2"></i>返回首页</a>';
@endphp
@extends('layouts.error')
