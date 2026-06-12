Feature:
    Ability to see the training dashboard in web

    Scenario: As an owner, I can access the training dashboard of my account
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given training session "Manipulación de alimentos" with id "session-1" of account "my-account" exists
        Given protocol "Protocolo A" with id "protocol-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/trainings/dashboard"
        Then the response status should be 200
        Then I see a text "Protocolos leídos"
        Then I see a text "Asistencias por sesión"

    Scenario: As an owner, I cannot see training data of other accounts in the dashboard
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given training session "Manipulación de alimentos" with id "session-1" of account "other-account" exists
        Given protocol "Protocolo A" with id "protocol-1" of account "other-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/trainings/dashboard"
        Then the response status should be 200
        Then I see a text "No hay datos de formación para los filtros seleccionados"

    Scenario: As a normal user, I can't access the training dashboard
        Given account "MyAccount" with id "my-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-account" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/trainings/dashboard"
        Then the response status should be 403

    Scenario: As an admin, I can't access the training dashboard
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/trainings/dashboard"
        Then the response status should be 403
