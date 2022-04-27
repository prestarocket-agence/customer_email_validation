{extends file='page.tpl'}

{block name='page_content_container'}
    <div id="custom-text">
        <h2>{l s='Your account is successfully activated' mod='customer_email_validation'}</h2>
        <p><strong class="dark">{l s="You account has been activated successfully." mod="customer_email_validation"} <a href="#">{l s="Login." mod="customer_email_validation"}</a></strong></p>
    </div>
{/block}
