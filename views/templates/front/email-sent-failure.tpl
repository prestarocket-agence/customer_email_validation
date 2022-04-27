{extends file='page.tpl'}

{block name='page_content_container'}
    <div id="custom-text">
        <h2>{l s='Something goes wrong. Email not sent.' mod='customer_email_validation'}</h2>
        <p><strong class="dark">{l s="Please try later. " mod="customer_email_validation"}</strong></p>
    </div>
{/block}