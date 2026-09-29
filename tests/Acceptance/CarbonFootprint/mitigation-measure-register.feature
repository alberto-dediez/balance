Feature:
    I want to make sure that the software allows to register a suggested mitigation measure

    Scenario: As an admin, I can register a new measure
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mitigation measure category "Energía" with id "C1" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measures/register"
        Then the response status should be 200
        When I submit a form with "name:Caldera eficiente|category:C1" and button "Registrar"
        Then I am redirected to route "admin_mitigation_measures_edit"
        Then I see a text "Medida creada correctamente: Caldera eficiente"

    Scenario: As owner I cannot register measures
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/mitigation-measures/register"
        Then the response status should be 403
