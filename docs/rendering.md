___
> ### Beware!
> These documents don't necessarily refer to _implemeted_  functionality.
> They serve mostly as an outlining of how the finished project will work.
___

## Page rendering ##

### Tokens

### Default context

path
:   the current requested path

js
:   the name of the js directories

css
:   the name of the css directories

meta
:   an array of [meta](meta) data collected for the current request or a string with
    one `<meta>` tag for each meta info collected for this path.

/
:   the basepath to the document root. ('/' unless in subfolder)

@
:   the name of attachments directories

scripts
:   an array of links to scripts collected for this page for php content or
    a string with one `<link>` html tag for each collected script. 



### Built-in token commands & aliases

#### partial

`{{partial:partial-name}}`

`{{+:partial-name}}`

Cascades directories to locate a partial named 'partial' and replaces
the token with its contents. Searches for the same filetypes as current
content. Partials will be processed recursively right before insertion.


#### attachment

`{{attachment: attachment-name.ext}}`

`{{att: attachment-name.ext}}`

`{{@: attachment-name.ext}}`

Cascades attachments directories to locate an attachment named
'attachment-name.ext' and replaces the tag with a link to that file.
If no file is found it points to 404 error.


#### script

`{{script: script-name}}`

`{{js: script-name}}`

`{{$: script-name}}`

Cascades script directories to locate a script named 'script-name' and
replaces the token with the path to that file.

#### image

`{{image: image-name}}`

`{{img: image-name}}`

`{{!: image-name}}`

Cascades image directories to locate an image named 'image-name' and replaces
the token with a the path to that image file.

#### meta

`{{meta: meta_attr_name}}`

`{{m: meta_attr_name}}`

Replaces token with meta value named 'meta\_attribute\_name'. If the value is
an array it returns a comma-separated list of the values.

#### style

`{{style: style-name}}`

`{{~:style}}`

Cascades style directories to locate a style named 'style' and replaces
the token with a link to that file.

#### table of contents

`{{toc: level}}`

Creates a table of contents (`<ul>` tag) at current path recursing at `level`
depth into subdirectories.

#### wiki

`{{wiki: wiki-page-name}}`

`{{w: wiki-page-name}}`

Searches for wiki-page-name and if it does not exist it creates
a page with that name (in the same format as the calling page) and adds
a header 'wiki-page-name' and 'No contents' content.

