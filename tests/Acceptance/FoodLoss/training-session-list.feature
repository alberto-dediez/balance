Feature:
        Ability to list training sessions in web

        Scenario: As owner I can access a list of my training sessions
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given training session "Session 1" with id "1111" of account "mycompany" exists
                Given training session "Session 2" with id "2222" of account "mycompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/training-sessions"
                Then the response status should be 200
                Then I see a table with 3 rows

        Scenario: As owner I cannot see training sessions from other account
                Given account "MyCompany" with id "mycompany" exists
                Given account "OtherCompany" with id "othercompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given training session "Session 1" with id "1111" of account "othercompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/training-sessions"
                Then the response status should be 200
                Then I see a table with 1 rows

        Scenario: As normal user I can access a list of training sessions from my account
                Given account "MyCompany" with id "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
                Given training session "Session 1" with id "1111" of account "mycompany" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/training-sessions"
                Then the response status should be 200
                Then I see a table with 2 rows

        Scenario: As admin I cannot access the training sessions list
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                When I am logged as "admin@dediez.software"
                When I go to "/es/training-sessions"
                Then the response status should be 403
