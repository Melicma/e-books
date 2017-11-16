<?php

/* login.twig */
class __TwigTemplate_085e480cf360a82e434c5bcfe6386e873a291b60ca89dd487468095301478d2f extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("home.phtml", "login.twig", 1);
        $this->blocks = array(
            'body' => array($this, 'block_body'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "home.phtml";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_body($context, array $blocks = array())
    {
        // line 4
        echo "    <h1>User List</h1>
    <div class=\"container\">
        <div class=\"row justify-content-md-center\">
            <form method=\"post\">
                <div class=\"form-group\">
                    <label for=\"exampleInputEmail1\">Email</label>
                    <input type=\"email\" class=\"form-control\" id=\"exampleInputEmail1\" aria-describedby=\"emailHelp\" name=\"email\" placeholder=\"Vložte email\">
                </div>
                <div class=\"form-group\">
                    <label for=\"exampleInputPassword1\">Heslo</label>
                    <input type=\"password\" class=\"form-control\" id=\"exampleInputPassword1\" name=\"password\" placeholder=\"Heslo\">
                </div>
                <!--        <div class=\"form-check\">-->
                <!--            <label class=\"form-check-label\">-->
                <!--                <input type=\"checkbox\" class=\"form-check-input\">-->
                <!--                Check me out-->
                <!--            </label>-->
                <!--        </div>-->
                <button type=\"submit\" class=\"btn btn-primary\">Login</button>
            </form>
        </div>
    </div>
";
    }

    public function getTemplateName()
    {
        return "login.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  31 => 4,  28 => 3,  11 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "login.twig", "/home/martin/Dokumenty/skola/ebooks/e-books/templates/twig/login.twig");
    }
}
