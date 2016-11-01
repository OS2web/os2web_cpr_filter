<?php
/**
 * @file
 * Contains Drupal\celebrate\Plugin\Filter\FilterCelebrate
 */

namespace Drupal\os2web_cpr_filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to find and replace CPR-numbers.
 *
 * @Filter(
 *   id = "filter_cpr",
 *   title = @Translation("OS2Web CPR-number filter"),
 *   description = @Translation("Custom filter searching texts for CPR-numbers to replace them."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "modulus11_check" = TRUE,
 *     "date_check" = TRUE,
 *     "replace_all_dash" = TRUE,
 *     "dummy_value" = "XXXXXX-XXXX"
 *   }
 * )
 */
class FilterCpr extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['modulus11_check'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Validate found numbers with modulus11?'),
      '#description' => $this->t('If enabled will only validated numbers be replaced with a dummy value.'),
      '#default_value' => $this->settings['modulus11_check'],
    );
    $form['date_check'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Validate date of found numbers if they fail modulus11 check?'),
      '#description' => $this->t('If enabled will non modulus11 valid numbers have their date-part validated and numbers with a valid date will be replaced.'),
      '#default_value' => $this->settings['date_check'],
    );
    $form['replace_all_dash'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Replace all numbers matching the format XXXXXX-XXXX with a dummy value.'),
      '#default_value' => $this->settings['replace_all_dash'],
    );
    $form['dummy_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Dummy value'),
      '#default_value' => $this->settings['dummy_value'],
      '#maxlength' => 32,
      '#description' => $this->t('The value we are replacing found CPR-numbers with.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Finding matches
    // To prevent false positives, match must not be preceded by a digit.
    // To prevent false positives, match must be followed by a word boundary.
    // Allow a separator character (whitespace|slash|dot|dash) between date-month and month-year part.
    // Allow a separation string (whitespace+slash|dot|dash+whitespace, or just whitespace) between date and serial part
    $pattern = "/(?<!\d)\d{2}([\s\/\.\-]?)\d{2}\\1\d{2}(?:\s{0,2}[\/\.-]\s{0,2}|\s{0,2})\d{4}\b/";
    preg_match_all($pattern, $text, $matches);

    // Looping through matches and replacing them
    $replace = array();

    foreach (current($matches) as $value) {
      // remove separator characters (anything except digits)
      $stripped_value = preg_replace('/[^\d]/','',$value);

      // Replacing all numbers containing a dash
      if ($this->settings['replace_all_dash'] && strpos($value, '-') !== FALSE) {
        $replace[$value] = $value;
      }
      // Modulus11 and date check
      else if(($this->settings['modulus11_check'] && $this->_filter_modulus11($stripped_value))
         || ($this->settings['date_check'] && $this->_filter_date_check($stripped_value))) {
        $replace[$value] = $value;
      }
    }

    // Replacing the CPR-numbers
    $text = str_replace($replace, $this->settings['dummy_value'], $text);

    return new FilterProcessResult($text);
  }

  /**
   * Validate a number against modulus 11.
   *
   * @param string $number
   *   The number being validated. Dash is stripped.
   *
   * @return bool
   */
  private function _filter_modulus11($number) {
    $factor = '432765432';
    $number_arr = str_split($number);
    $factor_arr = str_split($factor);
    $control = end($number_arr);
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
      $sum += $number_arr[$i] * $factor_arr[$i];
    }
    $remainder = $sum % 11;
    $result = 11 - $remainder;
    $result = ($result == 11) ? $result % 11 : $result;
    return ($control == $result);
  }

  /**
   * Validate the date part (first 6 characters) of a number.
   *
   * @param string $number
   *   The number being validated. Dash is stripped.
   *
   * @return bool
   */
  private function _filter_date_check($number) {
    $day = substr($number, 0, 2);
    $month = substr($number, 2, 2);
    $year = substr($number, 4, 2);
    $year_check = substr($number, 6, 1);

    // Finding century based on number 7.
    switch ($year_check) {
      case 0:
      case 1:
      case 2:
      case 3:
        // 1900-1999
        $prefix_year = 19;
        break;
      case 4:
        // 2000-2036 or 1937-1999
        $prefix_year = ($year > 36) ? 19 : 20;
        break;
      case 5:
      case 6:
      case 7:
      case 8:
        // 2000-2057 or 1858-1899
        $prefix_year = ($year > 57) ? 18 : 20;
        break;
      case 9:
        // 2000-2036 or 1937-1999
        $prefix_year = ($year > 37) ? 19 : 20;
        break;
      default:
        return FALSE;
    }

    return checkdate($month, $day, sprintf("%d%d", $prefix_year, $year));
  }

}