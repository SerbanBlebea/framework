<?php

namespace Framework\Commands;

use Framework\Interfaces\CommandInterface;
use Framework\Commands\Command;
use Framework\Injectables\Injector;
use Framework\Console\FileCreator;

class CreateRuleCommand extends Command implements CommandInterface
{
    private $payload;

    private $path = __APP_ROOT__ . "/../src/Rules";

    public function input($payload)
    {
        $this->payload = (strpos($payload, "Rule") !== false) ? ucfirst($payload) : ucfirst($payload) . "Rule";
        $this->completePath = $this->path . "/" . $this->payload . ".php";
        $this->process();
    }

    public function process()
    {
        if(file_exists($this->completePath) == true)
        {
            $this->output("error", "Error, file with this name already exists !");
        }

        $config = Injector::resolve("Config");
        $config = $config->getConfig("application");
        $namespace = $config["rule_namespace"];

        $content = "<?php \n\n" .
                   "namespace " . rtrim($namespace, "\\") . ";\n\n" .
                   "use Framework\Interfaces\RouterRuleInterface;\n" .
                   "use Framework\Injectables\Injector;\n" .
                   "use Framework\RouterRules\Rule;\n\n" .
                   "class " . $this->payload . " extends Rule implements RouterRuleInterface\n" .
                   "{\n" .
                       "\tpublic function apply(\$params = null)\n" .
                       "\t{\n" .
                           "\t\tif(true)\n" .
                           "\t\t{\n" .
                               "\t\t\t\$this->next();\n" .
                           "\t\t}\n" .
                       "\t}\n\n" .
                       "\tpublic function fail()\n" .
                       "\t{\n" .
                           "\t\tdd(\"failed rule\");\n" .
                       "\t}\n" .
                   "}";
        FileCreator::create($this->completePath, $content);

        $file = file_exists($this->completePath);
        if($file == true)
        {
            $this->output("success", "Success, " . $this->payload . " created !");
        } elseif($file == false) {
            $this->output("error", "Error, file was not created !");
        } else {
            echo "I have nothing to say...";
        }
    }
}
