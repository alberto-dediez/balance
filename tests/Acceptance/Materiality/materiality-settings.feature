Feature:
    I want to make sure that the software allows the administrator to configure the materiality relevance threshold

    Scenario: As an admin, I see the default materiality settings and the menu entry
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-settings"
        Then the response status should be 200
        Then I see a text "Configuración"
        Then I see a link to "/es/admin/materiality-settings"
        Then I see a form field "update_materiality_settings[relevanceThresholdPercent]" with value "50"
        Then materiality settings should not exist

    Scenario: As an admin, I can save the materiality threshold
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-settings"
        When I untick the checkbox "update_materiality_settings[highMagnitudeForcesInclusion]"
        When I submit a form with "relevanceThresholdPercent:35" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Configuración de materialidad guardada"
        Then I see a form field "update_materiality_settings[relevanceThresholdPercent]" with value "35"
        Then materiality settings should have threshold 35 and high magnitude rule "no"

    Scenario: As an admin, I can update the stored materiality threshold
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality settings with threshold 40 exist
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-settings"
        When I submit a form with "relevanceThresholdPercent:60" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then materiality settings should have threshold 60 and high magnitude rule "yes"

    Scenario: As an admin, I can't save a threshold out of range
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-settings"
        When I submit a form with "relevanceThresholdPercent:150" and button "Guardar"
        Then the response status should be 200
        Then I see a text "Este valor debería estar entre 1 y 100"
        Then I do not see a text "Configuración de materialidad guardada"
        Then materiality settings should not exist

    Scenario: As an owner, I can't configure the materiality threshold
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/materiality-settings"
        Then the response status should be 403
