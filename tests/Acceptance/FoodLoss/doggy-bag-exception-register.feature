Feature:
        I want to make sure the software allows registering a Doggy Bag exception

        Scenario: As an owner, I can access to register new Doggy Bag exceptions
                Given account "mycompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/doggy-bag-exceptions/register"
                Then the response status should be 200
                Then I see a form

        Scenario: As a normal user, I cannot register new Doggy Bag exceptions
                Given user "user1@dediez.software" with role "ROLE_USER" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/doggy-bag-exceptions/register"
                Then the response status should be 403

        Scenario: As an owner, I can register a new Doggy Bag exception
                Given account "mycompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given critical point "Buffet" with id "cp1" of account "mycompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/doggy-bag-exceptions/register"
                When I submit a form with "description:Razón sanitaria|criticalPointId:cp1" and button "Registrar"
                When I follow redirect
                Then the response status should be 200
                Then I see a text "Excepción Doggy Bag creada correctamente"
