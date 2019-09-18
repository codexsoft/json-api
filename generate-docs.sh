./bin/console app:swagen ./var/swagger/Definitions.php -vv
./vendor/bin/swagger -o ./var/swagger/api.swagger.json -e ./src/Command/GenerateSwaggerCommand.php  ./src ./var/swagger
npx -q redoc-cli bundle -o docs/api.html ./var/swagger/api.swagger.json --options.requiredPropsFirst