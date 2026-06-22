Feature:
    I want to make sure that the software allows requesting signatures for training attendance sheets

    Scenario: As an admin, I cannot access the request signature page
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/request-signature"
        Then the response status should be 403

    Scenario: As a normal user, I cannot access the request signature page
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/request-signature"
        Then the response status should be 403

    Scenario: As an owner, I can access the request signature page
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/request-signature"
        Then the response status should be 200
        Then I see a form
