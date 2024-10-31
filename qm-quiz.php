<?php
/**
 * QuizMeister. Developed by Chris Dennett (dessimat0r@gmail.com)
 * Donate by PayPal to dessimat0r@gmail.com.
 * Bitcoin: 1JrHT9F96GjHYHHNFmBN2oRt79DDk5kzHq
 */

class QuizMeister_QuizData {
	public $logged_in; // true if logged in, bool
	public $frm_quizmeister_q; // form version of quizmeister_q contents, null or int
	public $num_q; // no. of questions, int
	public $start_quiz; // true if quiz has been started (first question), bool
	public $nonce; // null or str
	public $done_quiz; // true if quiz done, bool
	public $quiz_end; // true if quiz finished (final question form posted), bool
	public $q_index; // curr. question index, null or int
	public $res_uid; // result user id, null or int
	public $res_score; // result score, null or int
	public $res_numq; // result no. of questions (might be different to db if quiz edited), null or int
	public $res_time; // result datetime, epoch secs, null or int
	public $ck_baduser; // true if user is 'bad' because they logged in twice with same cookies under different users, null or bool
	public $result_saved; // true if result saved to user meta, null or bool
	public $num_corr; // number of correct answers, null or int
	public $has_q_form; // true if there is a quiz form OR start quiz form (-> question 1) in POST, false otherwise

	public $last_q_rightans; // last q. right answer index, null or int
	public $last_a; // last q. answer index, null or int
	public $last_a_right; // last q. answer selection correct, null or bool
	public $last_q_text; // last q. answer text, null or string
	public $last_q_rightans_text; // last q. correct answer text, null or string
	public $last_q_explan; // last q. correct answer explanation, null or string
	public $last_q_index; // last question index, null or int

	public $q_text; // curr. question text, null or string
	public $q_sub; // curr. question subtext, null or string
	public $q_embed; // curr. question oembed url, null or string
	public $q_anum; // curr. question no. of answers
	public $q_rightans; // curr. question correct answer index
	public $a_text; // answer texts, null or array

	public $user; // logged-in user, null or wp user object
	public $post_id; // post id, int

    function __construct($user, $post_id) {
		$this->user    = $user;
		$this->post_id = $post_id;

		if (isset($_POST['qm-q']) && !is_numeric($_POST['qm-q']) && intval($_POST['qm-q'] < 0 || intval($_POST['qm-q'] > 50))) {
			echo "bad qm-q";
			return;
		}
		//echo "<p>post thumbnail id: ".get_post_thumbnail_id($post_id)."</p>";
		//echo "<p>".print_r($_POST)."</p>";

		$this->logged_in  = $user->exists();
		$this->frm_quizmeister_q   = isset($_POST['qm-q']) ? intval($_POST['qm-q']) : null;
		$this->num_q      = intval(get_post_meta($post_id, "_qm-qnum", true));
		$this->start_quiz = isset($_POST['start-quiz']);
		$this->nonce      = isset($_POST['qm-nonce']) ? $_POST['qm-nonce'] : null;
		$this->upddb      = isset($_GET['upddb']) ? ($_GET['upddb'] === 'true' ? true : false) : null;

		$this->has_q_form = $this->start_quiz || isset($this->frm_quizmeister_q);
		if ($this->has_q_form) {
			$this->last_q_index = $this->start_quiz ? null : $this->frm_quizmeister_q;
			// validate continue quiz nonce
			if (!$this->start_quiz && (!isset($this->nonce) || !wp_verify_nonce($this->nonce, 'p'.$post_id.'_continue_quiz_q'.$this->last_q_index))) {
                wp_die( __( 'Cheating?' ) );
			}
			$this->quiz_end   = !$this->start_quiz && ($this->last_q_index+1 >= $this->num_q);
			$this->q_index      = $this->quiz_end ? null : ($this->start_quiz ? 0 : (isset($this->last_q_index) ? $this->last_q_index+1 : null));
			if ($this->quiz_end) {
				// process number of correct questions from POST
				$this->num_corr = 0;
				for ($i = 0; $i < $this->num_q; $i++) {
					$q_rightans = intval(get_post_meta($this->post_id, "_qm-q-{$i}-rightans", true));
					$q_ans      = intval($_POST["qm-q-{$i}-a"]);
					if ($q_rightans === $q_ans) $this->num_corr++;
					//echo "<p>[i: {$i}, q_rightans: {$q_rightans}, q_ans: {$q_ans}, num_q: {$num_q}, num_corr: {$num_corr}]</p>";
				}
				$this->done_quiz = true;
				//$url = preg_replace("(^https?://)", "", get_site_url());
				$urlp = parse_url(get_site_url());
				$url  = $urlp['host'];
				$this->res_score = $this->num_corr;
				$this->res_numq  = $this->num_q;
				$this->res_time  = current_time( 'timestamp' );
				if (!isset($urlp['domain'])) $urlp['domain'] = null;
				// set quiz cookies
				setcookie("qm-quiz-{$post_id}-result-score", $this->res_score, 0, $urlp['path'], $urlp['domain'], false, false);
				setcookie("qm-quiz-{$post_id}-result-numq",  $this->res_numq,  0, $urlp['path'], $urlp['domain'], false, false);
				setcookie("qm-quiz-{$post_id}-result-time",  $this->res_time,  0, $urlp['path'], $urlp['domain'], false, false);
				if ($this->logged_in) {
					setcookie("qm-quiz-{$post_id}-result-uid", $user->ID, 0, $urlp['path'], $urlp['domain'], false, false);
				}
			}
		} else {
			if ($this->logged_in) {
				$this->res_uid = isset($_COOKIE["qm-quiz-{$post_id}-result-uid"]) ? $_COOKIE["qm-quiz-{$post_id}-result-uid"] : null;
				// invalidate cookie if logged in and not same user as cookie
				if (isset($this->res_uid)) $this->ck_baduser = intval($this->res_uid) !== $user->ID; // if not same, invalid
			}
			if (!$this->ck_baduser) {
				$m_res_score = isset($_COOKIE["qm-quiz-{$post_id}-result-score"]) ? $_COOKIE["qm-quiz-{$post_id}-result-score"] : null; // check cookies first
				if (isset($m_res_score)) {
					// we have cookies, quiz is done
					$this->done_quiz = true;
					$this->res_score = intval($m_res_score);
					$this->res_numq  = intval($_COOKIE["qm-quiz-{$post_id}-result-numq"]);
					$this->res_time  = intval($_COOKIE["qm-quiz-{$post_id}-result-time"]);
				}
			}
		}
		$this->in_quiz = $this->start_quiz || ($this->has_q_form && !$this->quiz_end);

		// do this if no quiz form, or quiz finished (with form) and we are logged in
		if (!$this->in_quiz && $this->logged_in) {
			if ($this->upddb || $this->quiz_end) {
				//TODO: check if this should be res
				//if ($this->res_score !== $this->num_corr || ($this->res_numq !== $this->num_q)) {
				$this->res_score = $this->num_corr;
				$this->res_numq  = $this->num_q;
				$this->res_time  = current_time( 'timestamp' );
				update_user_meta($user->ID, "_qm-quiz-{$post_id}-result-score", $this->res_score);
				update_user_meta($user->ID, "_qm-quiz-{$post_id}-result-numq",  $this->res_numq);
				update_user_meta($user->ID, "_qm-quiz-{$post_id}-result-time",  $this->res_time);
				$this->result_saved = true;
			} else {
				if (!isset($this->res_score)) {
					// do this if we have no quiz result set from cookies
					$m_res_score = get_user_meta($user->ID, "_qm-quiz-{$post_id}-result-score", true);
					// check if quiz result in db
					if (isset($m_res_score) && $m_res_score !== '') {
						// fetch score from db
						$this->done_quiz = true;
						$this->res_score = intval($m_res_score);
						$this->res_numq  = intval(get_user_meta($user->ID, "_qm-quiz-{$post_id}-result-numq", true));
						$this->res_time  = intval(get_user_meta($user->ID, "_qm-quiz-{$post_id}-result-time", true));
					}
				}
			}
		}

		// check for quiz form
		if ($this->has_q_form) {
			if (!$this->start_quiz) { // if not first question (no last question)
				// set last question stuff
				$this->last_q_rightans      = intval(get_post_meta($post_id, "_qm-q-{$this->last_q_index}-rightans", true));
				$this->last_a               = intval($_POST["qm-q-{$this->last_q_index}-a"]);
				$this->last_a_right         = $this->last_q_rightans === $this->last_a;
				$this->last_q_text          = get_post_meta($post_id, "_qm-q-{$this->last_q_index}-text", true);
				$this->last_q_rightans_text = get_post_meta($post_id, "_qm-q-{$this->last_q_index}-a-{$this->last_q_rightans}-text", true);
				$this->last_q_explan        = get_post_meta($post_id, "_qm-q-{$this->last_q_index}-explan", true);
			}
			if (!$this->quiz_end) { // if quiz hasn't ended (after final question)
				// set question stuff
				$this->q_text               = get_post_meta($post_id, "_qm-q-{$this->q_index}-text",  true);
				$this->q_sub                = get_post_meta($post_id, "_qm-q-{$this->q_index}-sub",   true);
				$this->q_embed              = get_post_meta($post_id, "_qm-q-{$this->q_index}-embed", true);
				$this->q_anum               = intval(get_post_meta($post_id, "_qm-q-{$this->q_index}-anum", true));
				$this->q_rightans           = intval(get_post_meta($post_id, "_qm-q-{$this->q_index}-rightans", true));
				// set answer texts
				for ($i = 0; $i < $this->q_anum; $i++) {
					$this->a_text[$i] = get_post_meta($this->post_id, "_qm-q-{$this->q_index}-a-{$i}-text", true);
				}
			}
		}

	}

	function output_answer_form_head() {
		?>
		<form id="qm-quiz-form" name="qm-quiz-form" method="post" action="<?php echo get_permalink(get_the_ID());?>">
			<?php wp_nonce_field('p'.$this->post_id.'_continue_quiz_q'.$this->q_index, 'qm-nonce'); ?>
			<input type="hidden" id="qm-q" name="qm-q" value="<?php echo $this->q_index; ?>">
			<?php
			for ($i = 0; $i < $this->q_index; $i++) {
				echo
					'<input type="hidden" id="qm-q-'.$i.'-a" name="qm-q-'.$i.'-a" value="'.
					($i === $this->last_q_index ? $this->last_a : intval($_POST["qm-q-{$i}-a"])).'">'
				;
			}
	}
}
?>
