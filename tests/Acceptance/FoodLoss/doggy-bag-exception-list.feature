Feature:
        Ability to list Doggy Bag exceptions in web

        Scenario: As an owner, I can access the list of my Doggy Bag exceptions
                Given account "mycompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given critical point "Buffet" with id "cp1" of account "mycompany" exists
                Given doggy bag exception "Excepción buffet" with id "1111" of account "mycompany" exists
                Given doggy bag exception "Otra excepción" with id "2222" of account "mycompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/doggy-bag-exceptions"
                Then the response status should be 200
                Then I see a table with 4 rows

        Scenario: As a normal user, I cannot access the list of Doggy Bag exceptions
                Given account "mycompany" with id "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/doggy-bag-exceptions"
                Then the response status should be 403

        Scenario: As an admin, I cannot access the list of Doggy Bag exceptions
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                When I am logged as "admin@dediez.software"
                When I go to "/es/doggy-bag-exceptions"
                Then the response status should be 403
