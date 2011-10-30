___
> ### Beware!
> These documents don't necessarily refer to _implemeted_  functionality.
> They serve mostly as an outlining of how the finished project will work.
___

## Meta ##

### Defining metas

    all:
        @title: Section title
        @author: Section author
        @toc: index
        @keywords: [example, section, more]
    
    page-1:
        @title: Section - Page 1
        @keywords: [page1, example1]
        
    page-2:
        @title: Page 2
    

### basic

`@title: Page title`

The current page title. Will render as a `<title>Page title</title>` tag in
`<head>`.

`@author: Page author name`

The current page author. Will render as a
`<meta name="author" value="Page author name"/>` tag in `<head>`.

`@description: Page contents description`

The page's description. Will render a
`<meta name="description" value="Page contents description"/>` tag in `<head>`.

`@keywords: [ keyword1, keyword2, ..., keywordN ]`

The page's keywords. Will render a
`<meta name="keywords" value="keyword1, keyword2, ..., keywordN "/>` tag in
`<head>`.

`@use: template-name`

Use template `template-name` to render this content. Will cascade to find a
partial named '_template-name' and render it replacing `{{content}}`
tokens with current page content.

### links

`@next: next-link-name`

Define a `<link rel="next">` for this page.

`@prev: previous-link-name`

Define a `<link rel="prev">` for this page.

`@contents: table-of-contents-name`

Define a `<link rel="contents">` for this page.

`@section: section-page-of-current-pages`

Define a `<link rel="section">` for this page. 

`@start: name-of-start-page`

Define a `<link rel="start">` for this page

`@lang: lang`

Language for the page.

`@translation: name-of-translation`

Define a `<link rel="relative" lang="[name-of-translation lang]">` link for this
page.