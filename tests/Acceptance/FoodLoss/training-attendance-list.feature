Feature:
    I want to make sure that the software shows training attendance correctly

    Scenario: As an owner I can see the attendances list for a training session
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances"
        Then the response status should be 200

    Scenario: As an owner I see all employees with and without attendance
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        Given employee "Alice" with id "emp-1" of account "mycompany" exists
        Given employee "Bob" with id "emp-2" of account "mycompany" exists
        Given training attendance for employee "emp-1" of session "ts-1" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: As a normal user I cannot see the attendances list
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances"
        Then the response status should be 403

    Scenario: As an admin I cannot see the attendances list
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances"
        Then the response status should be 403
