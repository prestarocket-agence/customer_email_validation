{extends file='page.tpl'}

{block name='page_content_container'}
    <div id="custom-text">
        <h2>{l s='Verification Email has been sent' mod='customer_email_validation'}</h2>
        <p><strong class="dark">{l s="Please check your email to activate your account. " mod="customer_email_validation"}</strong></p>
    </div>
{/block}