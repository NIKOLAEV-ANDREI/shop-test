<div class="panel">
    <div class="panel-heading">
        <i class="icon-comments"></i>
        {l s='Комментарии менеджеров' mod='managercomment'}
    </div>

    {if $manager_comment_error}
        <div class="alert alert-danger">
            {$manager_comment_error|escape:'html':'UTF-8'}
        </div>
    {/if}

    {if $manager_comment_success}
        <div class="alert alert-success">
            {$manager_comment_success|escape:'html':'UTF-8'}
        </div>
    {/if}

    <form
        action="{$manager_comment_form_action|escape:'html':'UTF-8'}"
        method="post"
    >
        <div class="form-group">
            <label for="manager_comment">
                {l s='Новый комментарий' mod='managercomment'}
            </label>

            <textarea
                id="manager_comment"
                name="manager_comment"
                class="form-control"
                rows="4"
                minlength="5"
                maxlength="500"
                required
            >{$manager_comment_value|escape:'html':'UTF-8'}</textarea>

            <p class="help-block">
                {l s='Допустимая длина: от 5 до 500 символов.' mod='managercomment'}
            </p>
        </div>

        <button
            type="submit"
            name="submitManagerComment"
            class="btn btn-primary"
        >
            <i class="icon-plus"></i>
            {l s='Добавить комментарий' mod='managercomment'}
        </button>
    </form>

    <hr>

    {if !$manager_comments}
        <p class="text-muted">
            {l s='Комментарии менеджеров пока не добавлены.' mod='managercomment'}
        </p>
    {else}
        {foreach from=$manager_comments item=manager_comment}
            <div class="well">
                <strong>
                    {$manager_comment.employee_name|escape:'html':'UTF-8'}
                </strong>

                <span class="text-muted">
                    {dateFormat date=$manager_comment.date_add full=true}
                </span>

                <p>
                    {$manager_comment.comment|escape:'html':'UTF-8'|nl2br}
                </p>
            </div>
        {/foreach}
    {/if}
</div>