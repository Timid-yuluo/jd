@php
    $errorCode = '500';
    $errorTitle = '服务器错误';
    $errorMessage = '服务器遇到了内部错误，请稍后重试。如果问题持续存在，请联系管理员。';
    $errorIcon = 'ti ti-alert-triangle';
    $errorCssFile = 'errors-500';
    $errorAction = '<a href="/" class="error-link"><i class="ti ti-home me-2"></i>返回首页</a> <a href="javascript:history.back()" class="error-link"><i class="ti ti-arrow-left me-2"></i>返回上一页</a>';
@endphp
@extends('layouts.error')
