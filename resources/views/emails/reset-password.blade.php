<x-mail::message>
# 重置您的密码

你好！

我们收到了您的密码重置请求。请点击下方按钮重置密码：

<x-mail::button :url="$url">
    重置密码
</x-mail::button>

如果按钮无法点击，请复制以下链接到浏览器中打开：

{{ $url }}

此链接将在 60 分钟后失效。如果您没有发起过密码重置请求，请忽略此邮件。

---

<p class="text-secondary small">如果您没有注册过 {{ config('app.name') }} 账号，请忽略此邮件。您的密码不会被更改。</p>
</x-mail::message>
