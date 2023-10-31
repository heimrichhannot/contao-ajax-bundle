
![](https://img.shields.io/packagist/v/heimrichhannot/contao-ajax-bundle.svg)
![](https://img.shields.io/packagist/dt/heimrichhannot/contao-ajax-bundle.svg)

# Contao Ajax Bundle

Ajax requests within contao are not centralized by default. Due to the handling of ajax requests within different types of modules 
a simple \Environment::get('isAjaxRequest') is not enough to delegate the request to the related module / method.
This module provides a global configuration, where you can attach your module within `$GLOBALS['AJAX']` as a custom group and 
add your actions with the required parameters that should be checked against the request.

## Technical instruction

The following section will show you how your custom ajax actions can be registered and triggered.

### 1. Configuration / Setup

The following example shows the [heimrichhannot/contao-formhybrid] (https://github.com/heimrichhannot/contao-formhybrid) ajax configuration.
```
config.php
/**
 * Ajax Actions
 */
$GLOBALS['AJAX'][\HeimrichHannot\FormHybrid\Form::FORMHYBRID_NAME] = array
(
	'actions' => array
	(
		'toggleSubpalette' => array
		(
			'arguments' => array('subId', 'subField', 'subLoad'),
			'optional'   => array('subLoad'),
		),
		'asyncFormSubmit'  => array
		(
			'arguments' => array(),
			'optional'   => array(),
		),
		'reload'  => array
		(
			'arguments' => array(),
			'optional'   => array(),
			'csrf_protection' => true, // cross-site request forgery (ajax token check)
		),
	),
);
```

As you can see, we have a group `formhybrid` that delegates all ajax request with this `group` parameter to formhybrid.
Then there are some actions `toggleSubpalette`, `asyncFormSubmit` and so on. These mehtod must be present with the same name in the delegated context.
You can provide arguments, that should be called within the function and added as arguments to the method. If the argument is `optional`, than the request
will be valid if the argument is not present, otherwise all `arguments` must be present in the request, to have a valid ajax request.
If you want to protect the ajax request against cross site violations, than add `csrf_protection => true` to your configuration and dont forget to update the ajax url on each request!

### 2. How can i create the url to my ajax action?

We provide a simple helper method within `HeimrichHannot\AjaxBundle\Manager\AjaxActionManager` that is called `generateUrl`. The following example shows, how we 
create the `toggleSubpalette` url within `FormHybrid`.

```
\Contao\System::getContainer()->get('huh.ajax.action')->generateUrl(Form::FORMHYBRID_NAME, 'toggleSubpalette')
```

As you can see, we do not add the arguments by default. This is done within the associated javascript code for `toggleSubpalette`.
You have to check within your javascript code, that the arguments `subId`, `subField`, `subLoad` are provided as $_POST parameters within the 
ajax request by your own.

```
jquery.formhybrid.js

toggleSubpalette: function (el, id, field, url) {
    el.blur();
    var $el = $(el),
        $item = $('#' + id),
        $form = $el.closest('form'),
        checked = true;

    var $formData = $form.serializeArray();

    $formData.push(
        {name: 'FORM_SUBMIT', value: $form.attr('id')},
        {name: 'subId', value: id},
        {name: 'subField',value: field});

    if ($el.is(':checkbox') || $el.is(':radio')) {
        checked = $el.is(':checked');
    }

    if (checked === false) {

        $.ajax({
            type: 'post',
            url: url,
            dataType: 'json',
            data: $formData,
            success: function (response) {
                $item.remove();
            }
        });

        return;
    }

    $formData.push(
        {name: 'subLoad', value: 1}
    );

    $.ajax({
        type: 'post',
        url: url,
        dataType: 'json',
        data: $formData,
        success: function (response, textStatus, jqXHR) {
            $item.remove();
            // bootstrapped forms
            if ($el.closest('form').find('.' + field).length > 0) {
                // always try to attach subpalette after wrapper element from parent widget
                $el.closest('form').find('.' + field).eq(0).after(response.result.html);
            } else {
                $el.closest('#ctrl_' + field).after(response.result.html);
            }
        }
    });
}
```

### 3. How are my ajax actions triggered?

The give you the biggest possible freedom, we decided to be always within contao context. Therefore all request preconditions are checked within the
default contao request-cycle.

In case of the `toggleSubpalette` example we trigger the action within `DC_Hybrid::__construct()` and provide a `new FormAjax($this)` as Response Context.

CAUTION: Don't call "runActiveAction" in generate() of a Contao module since that's too late. It's always best to run it in __construct().

```
DC_Hybrid.php

public function __construct($strTable = '', $varConfig = null, $intId = 0)
{
    ...
    
    \Contao\System::getContainer()->get('huh.ajax')->runActiveAction(Form::FORMHYBRID_NAME, 'toggleSubpalette', new FormAjax($this));
    
    ...
    
}
```

#### So what is done here? 

1. The ajax action is requested from your javascript code.
2. The ajax action is delegated to the current page, where our `DC_Hybrid` extended Module is available.
3. The `Ajax` Controller will check the request against the given parameters for `toggleSubpalette` from the `$GLOBALS['AJAX']` config.
4. If all requirements were meet, and the method `toggleSubpalette` is available within the given context `new FormAjax($this)`, the method is trigged with the given arguments.
5. Now you can do your module related stuff within `toggleSubpalette` 
6. If you want to return data to the ajax request, then you have to return a valid `HeimrichHannot\AjaxBundle\Response\Response` Object.
7. The returned `HeimrichHannot\AjaxBundle\Response\Response` Object will be converted to a JSON Object and the request will end here.

## Response Objects

Currently we implemented three response objects.

1. `HeimrichHannot\AjaxBundle\Response\ResponseSuccess`
2. `HeimrichHannot\AjaxBundle\Response\ResponseError`
3. `HeimrichHannot\AjaxBundle\Response\ResponseRedirect`

### ResponseSuccess

This will return a JSON Object with the HTTP-Statuscode `HTTP/1.1 200 OK` to the ajax action.

#### Example: 

##### Client-Side:
```
$.ajax({
    type: 'post',
    url: url,
    dataType: 'json',
    data: $formData,
    success: function (response, textStatus, jqXHR) {
        $item.remove();
        // bootstrapped forms
        if ($el.closest('form').find('.' + field).length > 0) {
            // always try to attach subpalette after wrapper element from parent widget
            $el.closest('form').find('.' + field).eq(0).after(response.result.html);
        } else {
            $el.closest('#ctrl_' + field).after(response.result.html);
        }
    }
});
```

##### Server-Side:
```
/**
 * Toggle Subpalette
 * @param      $id
 * @param      $strField
 * @param bool $blnLoad
 *
 * @return ResponseError|ResponseSuccess
 */
function toggleSubpalette($id, $strField, $blnLoad = false)
{
    $varValue = \Contao\System::getContainer()->get('huh.request')->getPost($strField) ?: 0;
    
    if (!is_array($this->dca['palettes']['__selector__']) || !in_array($strField, $this->dca['palettes']['__selector__'])) {
        \Controller::log('Field "' . $strField . '" is not an allowed selector field (possible SQL injection attempt)', __METHOD__, TL_ERROR);
        
        return new ResponseError();
    }
    
    $arrData = $this->dca['fields'][$strField];
    
    if (!Validator::isValidOption($varValue, $arrData, $this->dc)) {
        \Controller::log('Field "' . $strField . '" value is not an allowed option (possible SQL injection attempt)', __METHOD__, TL_ERROR);
        
        return new ResponseError();
    }
    
    if (empty(FormHelper::getFieldOptions($arrData, $this->dc))) {
        $varValue = (intval($varValue) ? 1 : '');
    }
    
    $this->dc->activeRecord->{$strField} = $varValue;
    
    $objResponse = new ResponseSuccess();
    
    if ($blnLoad)
    {
        $objResponse->setResult(new ResponseData($this->dc->edit(false, $id)));
    }
    
    return $objResponse;
}
```

### ResponseError

This will return a JSON Object with the HTTP-Statuscode `HTTP/1.1 400 Bad Request` to the ajax action.

#### Example: 

##### Client-Side:
```
 $.ajax({
    url: url ? url : $form.attr('action'),
    dataType: 'json',
    data: $formData,
    method: $form.attr('method'),
    error: function(jqXHR, textStatus, errorThrown){
        if (jqXHR.status == 400) {
            alert(jqXHR.responseJSON.message);
            return;
        }
    }
});
```

##### Server-Side:
```
$objResponse = new ResponseRedirect();
$objResponse->setUrl($strUrl);
return $objResponse;
```

### ResponseRedirect

This will return a JSON Object with the HTTP-Statuscode `HTTP/1.1 301 Moved Permanently` to the ajax action.
The redirect url is provided within the xhr response object `result.data.url`;

#### Example: 

##### Client-Side:
```
 $.ajax({
    url: url ? url : $form.attr('action'),
    dataType: 'json',
    data: $formData,
    method: $form.attr('method'),
    error: function(jqXHR, textStatus, errorThrown){
        if (jqXHR.status == 301) {
            location.href = jqXHR.responseJSON.result.data.url;
            return;
        }
    }
});
```

##### Server-Side:
```
$objResponse = new ResponseRedirect();
$objResponse->setUrl($strUrl);
die(json_encode($objResponse));
```

## Unit Testing

For unit testing, define the variable `UNIT_TESTING` as `true` within the $GLOBALS.

```
//bootstrap.php

define('UNIT_TESTING', true);
```

Than you are able to catch the ajax result within you test, by catching the `HeimrichHannot\AjaxBundle\Exception\AjaxExitException`.

```
// MyTestClass.php

    /**
     * @test
     */
    public function myTest()
    {
        $objRequest = \Contao\System::getContainer()->get('huh.request')->create('http://localhost' . AjaxAction::generateUrl('myAjaxGroup', 'myAjaxAction'), 'post');

        $objRequest->headers->set('X-Requested-With', 'XMLHttpRequest'); // xhr request

        Request::set($objRequest);

		$objForm = new TestPostForm();

        try
        {
            $objForm->generate();
            // unreachable code: if no exception is thrown after form was created, something went wrong
            $this->expectException(\HeimrichHannot\Ajax\Exception\AjaxExitException::class);
        } catch (AjaxExitException $e)
        {
            $objJson = json_decode($e->getMessage());

            $this->assertTrue(strpos($objJson->result->html, 'id="my_css_id"') > 0); // check that id is present within response
        }
    }
```