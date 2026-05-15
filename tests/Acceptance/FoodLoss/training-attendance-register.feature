Feature:
    I want to make sure that the software allows registering training attendances

    Scenario: As an admin, I can't access the register attendance page
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/register"
        Then the response status should be 403

    Scenario: As a normal user, I can't access the register attendance page
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/register"
        Then the response status should be 403

    Scenario: As an owner, I can access the register attendance form
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As an owner, I can register attendances with a PDF
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "First Aid Training" with id "ts-1" of account "mycompany" exists
        Given employee "Alice" with id "emp-1" of account "mycompany" exists
        Given a dummy PDF file exists in "tests/Acceptance/FoodLoss/test.pdf"
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/ts-1/attendances/register"
        When I attach the file "tests/Acceptance/FoodLoss/test.pdf" to "attendanceSheet"
        When I submit a form with "attendedAt:2024-03-15|employeeIds:emp-1" and button "Registrar asistencias"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Asistencias registradas correctamente"
