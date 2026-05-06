Feature:
        Ability to view the detail of a specific prevention plan version

        Scenario: As owner I can access a version detail of my prevention plan
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given prevention plan "Plan 1" with id "1111" of account "mycompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/prevention-plans/history/1111/1"
                Then the response status should be 200

        Scenario: As owner I cannot access a version detail of a prevention plan from another account
                Given account "MyCompany" with id "mycompany" exists
                Given account "OtherCompany" with id "othercompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given prevention plan "Plan 1" with id "1111" of account "othercompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/prevention-plans/history/1111/1"
                Then the response status should be 403

        Scenario: As admin I cannot access a version detail of a prevention plan
                Given account "MyCompany" with id "mycompany" exists
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                Given prevention plan "Plan 1" with id "1111" of account "mycompany" exists
                When I am logged as "admin@dediez.software"
                When I go to "/es/prevention-plans/history/1111/1"
                Then the response status should be 403

        Scenario: As normal user I cannot access a version detail of a prevention plan
                Given account "MyCompany" with id "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
                Given prevention plan "Plan 1" with id "1111" of account "mycompany" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/prevention-plans/history/1111/1"
                Then the response status should be 403
