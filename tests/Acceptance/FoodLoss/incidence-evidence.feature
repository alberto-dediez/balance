Feature:
    I want to make sure that the software manages incidence evidences correctly

    Scenario: As an owner, the edit page shows existing evidence
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given property "Test Property" with id "prop-1" of account "mycompany" exists
        Given incidence "Broken equipment" with id "inc-1" of account "mycompany" with evidence "ev-1" at "incidence_evidences/mycompany/report.pdf" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/incidences/edit/inc-1"
        Then the response status should be 200
        Then I see a text "report.pdf"

    Scenario: As an owner, I can download an evidence of my incidence
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given property "Test Property" with id "prop-1" of account "mycompany" exists
        Given incidence "Broken equipment" with id "inc-1" of account "mycompany" with evidence "ev-1" at "incidence_evidences/mycompany/report.pdf" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/incidences/inc-1/evidence/ev-1/download"
        Then the response status should be 200

    Scenario: As an owner, I cannot download an evidence from another account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given property "Test Property" with id "prop-1" of account "othercompany" exists
        Given incidence "Broken equipment" with id "inc-1" of account "othercompany" with evidence "ev-1" at "incidence_evidences/othercompany/report.pdf" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/incidences/inc-1/evidence/ev-1/download"
        Then the response status should be 403

    Scenario: As an owner, editing an incidence retains existing evidences
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given property "Test Property" with id "prop-1" of account "mycompany" exists
        Given incidence "Broken equipment" with id "inc-1" of account "mycompany" with evidence "ev-1" at "incidence_evidences/mycompany/report.pdf" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/incidences/edit/inc-1"
        When I submit a form with "name:Updated name" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then incidence "inc-1" should have 1 evidences

    Scenario: As an owner, I can register an incidence with an evidence
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given property "Test Property" with id "prop-1" of account "mycompany" exists
        Given a dummy PDF file exists in "tests/Acceptance/FoodLoss/test.pdf"
        When I am logged as "owner@dediez.software"
        When I go to "/es/incidences/register"
        When I submit the form with "name:Test Incidence|incidenceType:machinery_failure|description:A description|effect:An effect|propertyId:prop-1" and button "Registrar" adding evidence file "tests/Acceptance/FoodLoss/test.pdf"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Incidencia creada correctamente: Test Incidence"

    Scenario: As an owner, I can add an evidence when editing an incidence
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given property "Test Property" with id "prop-1" of account "mycompany" exists
        Given incidence "Broken equipment" with id "inc-1" of account "mycompany" exists
        Given a dummy PDF file exists in "tests/Acceptance/FoodLoss/test.pdf"
        When I am logged as "owner@dediez.software"
        When I go to "/es/incidences/edit/inc-1"
        When I submit the form with "name:Broken equipment" and button "Guardar" adding evidence file "tests/Acceptance/FoodLoss/test.pdf"
        When I follow redirect
        Then the response status should be 200
        Then incidence "inc-1" should have 1 evidences

    Scenario: As an owner, I can remove all evidences when editing an incidence
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given property "Test Property" with id "prop-1" of account "mycompany" exists
        Given incidence "Broken equipment" with id "inc-1" of account "mycompany" with evidence "ev-1" at "incidence_evidences/mycompany/report.pdf" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/incidences/edit/inc-1"
        When I submit the form with "name:Broken equipment" and button "Guardar" removing all evidences
        When I follow redirect
        Then the response status should be 200
        Then incidence "inc-1" should have 0 evidences
