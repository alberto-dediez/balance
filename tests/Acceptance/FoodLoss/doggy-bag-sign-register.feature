Feature:
        I want to make sure the software allows registering a Cartel Doggy Bag

        Scenario: As an owner, I can access the register form
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/doggy-bag-signs/register"
                Then the response status should be 200
                Then I see a form

        Scenario: As a normal user, I cannot register a Doggy Bag sign
                Given account "MyCompany" with id "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/doggy-bag-signs/register"
                Then the response status should be 403

        Scenario: As an admin, I cannot register a Doggy Bag sign
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                When I am logged as "admin@dediez.software"
                When I go to "/es/doggy-bag-signs/register"
                Then the response status should be 403
