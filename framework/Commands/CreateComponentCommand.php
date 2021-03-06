<?php

namespace Framework\Commands;

use Framework\Interfaces\CommandInterface;
use Framework\Commands\Command;
use Framework\Injectables\Injector;
use Framework\Console\FileCreator;

class CreateComponentCommand extends Command implements CommandInterface
{
    private $payload;

    private $path = __APP_ROOT__ . "/../src/Managers";

    public function input($payload)
    {
        $this->payload = (strpos($payload, "Component") !== false) ? ucfirst($payload) : ucfirst($payload) . "Component";
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
        $namespace = $config["component_namespace"];

        $content = "<?php \n\n" .
                   "namespace " . rtrim($namespace, "\\") . ";\n\n" .
                   "use Framework\Injectables\Injector;\n" .
                   "use Framework\Interfaces\ComponentInterface;\n\n" .
                   "class " . $this->payload . " extends Injector implements ComponentInterface\n" .
                   "{\n" .
                       "\tpublic function boot()\n" .
                       "\t{\n" .
                            "\t\tself::register(\"NewComponent\", function() {\n" .
                               "\t\t\t\$comp = new New();\n" .
                               "\t\t\treturn \$comp;\n" .
                           "\t\t});\n" .
                       "\t}\n\n" .
                       "\tpublic function run(\$instance)\n" .
                       "\t{\n\n" .
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
