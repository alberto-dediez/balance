Feature:
    I want to make sure that the software allows removing training attendances

    Scenario: As an owner, I can remove an attendance
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        Given training attendance "att-1" for employee "emp-1" of session "ts-1" of account "mycompany" at "training_attendances/mycompany/sheet.pdf" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/att-1/remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la asistencia"

    Scenario: As a normal user, I cannot remove an attendance
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        Given training attendance "att-1" for employee "emp-1" of session "ts-1" of account "mycompany" at "training_attendances/mycompany/sheet.pdf" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/att-1/remove"
        Then the response status should be 403

    Scenario: An owner cannot remove an attendance from another account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "othercompany" exists
        Given training attendance "att-1" for employee "emp-1" of session "ts-1" of account "othercompany" at "training_attendances/othercompany/sheet.pdf" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/att-1/remove"
        Then the response status should be 403
