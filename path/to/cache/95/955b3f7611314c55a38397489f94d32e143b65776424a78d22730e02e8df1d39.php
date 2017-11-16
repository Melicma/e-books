<?php

/* home.phtml */
class __TwigTemplate_0716a74f8d2bdd850868dbaca2a6123995c5f154642066031d9b6a6b08257eef extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"utf-8\"/>
    <title>Ebooks</title>
    <link rel=\"shortcut icon\" href=\"//www.freefavicon.com/freefavicons/icons/book-icon-152-191918.png\" type=\"image/x-icon\">
    <script src=\"//code.jquery.com/jquery-3.2.1.slim.min.js\"></script>
    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js\"></script>
    <script src=\"https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js\"></script>
    <link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css\" integrity=\"sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb\" crossorigin=\"anonymous\">
    <!--    <link href='//fonts.googleapis.com/css?family=Lato:300' rel='stylesheet' type='text/css'>-->
</head>
<body>

</body>
</html>
";
    }

    public function getTemplateName()
    {
        return "home.phtml";
    }

    public function getDebugInfo()
    {
        return array (  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "home.phtml", "/home/martin/Dokumenty/skola/ebooks/e-books/templates/twig/home.phtml");
    }
}
