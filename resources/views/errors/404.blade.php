@php
    $errorCode = '404';
    $errorTitle = '页面未找到';
    $errorMessage = '您访问的页面不存在或已被移除，请检查网址是否正确。';
    $errorIcon = 'ti ti-file-unknown';
    $errorCssFile = 'errors-404';
    $errorAction = '<a href="/" class="error-link"><i class="ti ti-home me-2"></i>返回首页</a> <a href="javascript:history.back()" class="error-link"><i class="ti ti-arrow-left me-2"></i>返回上一页</a>';
@endphp
@extends('layouts.error')
