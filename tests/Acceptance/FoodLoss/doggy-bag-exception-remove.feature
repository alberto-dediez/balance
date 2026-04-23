Feature:
        I want to make sure the system allows removing a Doggy Bag exception

        Scenario: As an owner, I can remove my Doggy Bag exception
                Given account "mycompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given doggy bag exception "Excepción buffet" with id "333" of account "mycompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/doggy-bag-exceptions/remove/333"
                When I follow redirect
                Then the response status should be 200
                Then I see a text "Se ha eliminado la excepción Doggy Bag"

        Scenario: As a normal user, I cannot remove a Doggy Bag exception
                Given account "mycompany" with id "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
                Given doggy bag exception "Excepción buffet" with id "333" of account "mycompany" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/doggy-bag-exceptions/remove/333"
                Then the response status should be 403
